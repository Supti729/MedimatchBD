<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\MedicineEligibility;
use App\Enums\RequestStatus;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\RequestMatch;
use App\Notifications\MatchFound;
use App\Services\Ai\MedicineText;
use App\Services\Ai\SemanticRanker;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Matching between medicine requests and listings.
 *
 * 1. HARD RULES (always, in Laravel): same medicine identity, exact strength, exact dosage form,
 *    publicly available, not the requester's own listing, the requester's assistance preference.
 * 2. RELEVANCE SCORE (0–100) for candidates that passed the rules:
 *      70 identity passed + up to 15 for how closely the wording fits + 10/5 same district/division + 3 recent.
 *    "How closely the wording fits" comes from the AI service when it is enabled (method "semantic"),
 *    otherwise from a keyword rule (method "keyword").
 * 3. AI IDENTITY SUGGESTION for free-text requests only (method "semantic_identity"): see suggestIdentity().
 *
 * Relevance is NOT medical suitability or equivalence, and the AI never overrides the hard rules.
 */
class MatchingService
{
    public function __construct(private SemanticRanker $ai) {}

    /** Find and store matches for one request; notify the requester about new ones. */
    public function matchRequest(MedicineRequest $request): Collection
    {
        if (! $request->status->isOpen()) {
            return collect();
        }
        $request->loadMissing('location');

        $candidates = MedicineListing::publiclyVisible()->withCardData()
            ->where('status', ListingStatus::Available)
            ->where('user_id', '!=', $request->user_id)
            ->get()
            ->filter(fn ($listing) => $this->passesHardRules($request, $listing))
            ->values();

        $semantic = $this->ai->rank(MedicineText::request($request), $candidates->mapWithKeys(fn ($l) => [$l->id => MedicineText::listing($l)])->all());

        return $candidates->map(fn ($listing) => $this->store($request, $listing, $semantic[(string) $listing->id]['score'] ?? null))->filter();
    }

    /** When a listing becomes available, check every open request against it. */
    public function matchListing(MedicineListing $listing): void
    {
        if ($listing->status !== ListingStatus::Available) {
            return;
        }
        $listing->loadMissing('medicine.aliases', 'location');

        $requests = MedicineRequest::with('location', 'owner')
            ->whereIn('status', [RequestStatus::Active, RequestStatus::Matching, RequestStatus::MatchFound])
            ->where('user_id', '!=', $listing->user_id)
            ->where(fn ($q) => $q->where('medicine_id', $listing->medicine_id)
                ->orWhere('suggested_medicine_id', $listing->medicine_id)
                ->orWhere('generic_name', $listing->generic)
                ->orWhere(fn ($free) => $free->whereNull('medicine_id')->where('dosage_form', $listing->form)))
            ->get()
            ->filter(fn ($request) => $this->passesHardRules($request, $listing))
            ->values();

        $semantic = $this->ai->rank(MedicineText::listing($listing), $requests->mapWithKeys(fn ($r) => [$r->id => MedicineText::request($r)])->all());
        $requests->each(fn ($request) => $this->store($request, $listing, $semantic[(string) $request->id]['score'] ?? null));
    }

    public function passesHardRules(MedicineRequest $request, MedicineListing $listing): bool
    {
        return $this->identityMethod($request, $listing) !== null
            && $this->preferenceAllows($request, $listing)
            && $listing->user_id !== $request->user_id;
    }

    /**
     * How the medicine identity was established, or null if it was not:
     *   "catalogue"  the request chose this catalogue medicine (or one with the same generic, strength and form)
     *   "exact"      free text whose generic/brand name, strength and form equal the listing's
     *   "suggested"  free text where the AI suggested this catalogue medicine, AND strength and form are equal
     */
    public function identityMethod(MedicineRequest $request, MedicineListing $listing): ?string
    {
        $sameStrengthAndForm = self::norm($request->strength) === self::norm($listing->strength)
            && self::norm($request->dosage_form) === self::norm($listing->form);

        if ($request->medicine_id) {
            return $request->medicine_id === $listing->medicine_id
                || (self::norm($request->generic) === self::norm($listing->generic) && $sameStrengthAndForm) ? 'catalogue' : null;
        }
        if (! $sameStrengthAndForm) {
            return null;
        }
        $name = self::norm($request->generic_name ?: $request->medicine_name);
        if ($name !== '' && in_array($name, [self::norm($listing->generic), self::norm($listing->name)], true)) {
            return 'exact';
        }

        return $request->suggested_medicine_id && $request->suggested_medicine_id === $listing->medicine_id ? 'suggested' : null;
    }

    /**
     * For a free-text request, ask the AI which catalogue medicine was most likely meant.
     * Safety rules: only catalogue entries that may be listed, with EXACTLY the request's strength and
     * dosage form, are considered; the best one must reach the similarity threshold AND be clearly ahead
     * of the best candidate with a different active ingredient. Otherwise nothing is suggested.
     */
    public function suggestIdentity(MedicineRequest $request): void
    {
        $request->forceFill(['suggested_medicine_id' => null, 'suggestion_similarity' => null]);

        if (! $request->medicine_id && $this->ai->enabled()) {
            $candidates = Medicine::with('aliases')->where('is_active', true)
                ->where('eligibility', '!=', MedicineEligibility::NotAllowed)->get()
                ->filter(fn ($m) => self::norm($m->strength) === self::norm($request->strength) && self::norm($m->dosage_form) === self::norm($request->dosage_form))
                ->keyBy('id');

            $ranked = $this->ai->rank(MedicineText::request($request), $candidates->map(fn ($m) => MedicineText::medicine($m))->all());
            if ($ranked) {
                $topId = array_key_first($ranked);
                $top = $ranked[$topId];
                $runnerUp = collect($ranked)->first(fn ($r, $id) => self::norm($candidates[$id]->generic_name) !== self::norm($candidates[$topId]->generic_name));
                $clearlyAhead = ! $runnerUp || config('services.ai.identity_margin') <= $top['similarity'] - $runnerUp['similarity'];

                if ($top['similarity'] >= config('services.ai.identity_threshold') && $clearlyAhead) {
                    $request->forceFill(['suggested_medicine_id' => (int) $topId, 'suggestion_similarity' => $top['similarity']]);
                }
            }
        }
        $request->save();
    }

    /** Relevance 0–100. $semanticScore is the AI's 0–100 wording similarity, or null for keyword scoring. */
    public function score(MedicineRequest $request, MedicineListing $listing, ?int $semanticScore = null): int
    {
        $score = 70; // passed the strict identity rules
        if ($semanticScore !== null) {
            $score += (int) round(15 * $semanticScore / 100);
        } elseif ($request->medicine_id === $listing->medicine_id || self::norm($request->medicine_name) === self::norm($listing->name)) {
            $score += 15;
        }
        if ($request->location?->district === $listing->district) {
            $score += 10;
        } elseif ($request->location?->division === $listing->division) {
            $score += 5;
        }

        return min(100, $score + ($listing->published_at?->gt(now()->subWeek()) ? 3 : 0));
    }

    private function preferenceAllows(MedicineRequest $request, MedicineListing $listing): bool
    {
        return match ($request->preference) {
            'donation' => $listing->type === ListingType::Donation,
            'affordable_access' => $listing->type === ListingType::AffordableAccess,
            default => true,
        };
    }

    private function store(MedicineRequest $request, MedicineListing $listing, ?int $semanticScore): ?RequestMatch
    {
        $method = match (true) {
            $this->identityMethod($request, $listing) === 'suggested' => 'semantic_identity',
            $semanticScore !== null => 'semantic',
            default => 'keyword',
        };

        $match = RequestMatch::firstOrNew(['medicine_request_id' => $request->id, 'medicine_listing_id' => $listing->id]);
        $match->fill(['score' => $this->score($request, $listing, $semanticScore), 'method' => $method])->save();

        if (! $match->notified_at) {
            $request->owner->notify(new MatchFound($request, $listing));
            $match->forceFill(['notified_at' => now()])->save();
        }
        if (in_array($request->status, [RequestStatus::Active, RequestStatus::Matching], true)) {
            $request->status = RequestStatus::MatchFound;
            $request->save();
        }

        return $match;
    }

    public static function norm(?string $text): string
    {
        $text = Str::lower((string) $text);
        $text = preg_replace('/(\d)\s*(mg|ml|mcg|iu|g)\b/', '$1$2', $text);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\/. ]+/u', ' ', $text)));
    }
}
