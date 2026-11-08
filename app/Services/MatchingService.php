<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\RequestStatus;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\RequestMatch;
use App\Notifications\MatchFound;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rule-based (keyword) matching between requests and listings.
 *
 * Hard rules ALWAYS apply first: same medicine identity (catalogue entry, or same
 * generic name), same strength, same dosage form, eligible & publicly available,
 * not the requester's own listing, and the requester's assistance preference.
 * Only then is a relevance score (0–100) computed. Part 3 replaces score() with
 * semantic similarity from the AI service; the hard rules stay here, in Laravel.
 *
 * Relevance is NOT medical suitability or equivalence.
 */
class MatchingService
{
    /** Find and store matches for one request; notify the requester about new ones. */
    public function matchRequest(MedicineRequest $request): Collection
    {
        if (! $request->status->isOpen()) {
            return collect();
        }

        $candidates = MedicineListing::publiclyVisible()->withCardData()
            ->where('status', ListingStatus::Available)
            ->where('user_id', '!=', $request->user_id)
            ->get()
            ->filter(fn ($listing) => $this->passesHardRules($request, $listing));

        return $candidates->map(fn ($listing) => $this->store($request, $listing))->filter();
    }

    /** When a listing becomes available, check every open request against it. */
    public function matchListing(MedicineListing $listing): void
    {
        if ($listing->status !== ListingStatus::Available) {
            return;
        }
        $listing->loadMissing('medicine', 'location');

        MedicineRequest::with('location')
            ->whereIn('status', [RequestStatus::Active, RequestStatus::Matching, RequestStatus::MatchFound])
            ->where('user_id', '!=', $listing->user_id)
            ->where(fn ($q) => $q->where('medicine_id', $listing->medicine_id)->orWhere('generic_name', $listing->generic))
            ->get()
            ->filter(fn ($request) => $this->passesHardRules($request, $listing))
            ->each(fn ($request) => $this->store($request, $listing));
    }

    public function passesHardRules(MedicineRequest $request, MedicineListing $listing): bool
    {
        $sameIdentity = $request->medicine_id
            ? $request->medicine_id === $listing->medicine_id
                || (self::norm($request->generic) === self::norm($listing->generic) && self::norm($request->strength) === self::norm($listing->strength) && self::norm($request->dosage_form) === self::norm($listing->form))
            : self::norm($request->generic_name ?: $request->medicine_name) !== '' && in_array(self::norm($request->generic_name ?: $request->medicine_name), [self::norm($listing->generic), self::norm($listing->name)], true)
                && self::norm($request->strength) === self::norm($listing->strength)
                && self::norm($request->dosage_form) === self::norm($listing->form);

        $preferenceOk = match ($request->preference) {
            'donation' => $listing->type === ListingType::Donation,
            'affordable_access' => $listing->type === ListingType::AffordableAccess,
            default => true,
        };

        return $sameIdentity && $preferenceOk && $listing->user_id !== $request->user_id;
    }

    /** Keyword relevance score. Replaced by semantic similarity in Part 3. */
    public function score(MedicineRequest $request, MedicineListing $listing): int
    {
        $score = 70; // passed the strict identity rules
        if ($request->medicine_id === $listing->medicine_id || self::norm($request->medicine_name) === self::norm($listing->name)) {
            $score += 15;
        }
        if ($request->location?->district === $listing->district) {
            $score += 10;
        } elseif ($request->location?->division === $listing->division) {
            $score += 5;
        }

        return min(100, $score + ($listing->published_at?->gt(now()->subWeek()) ? 3 : 0));
    }

    private function store(MedicineRequest $request, MedicineListing $listing): ?RequestMatch
    {
        $match = RequestMatch::firstOrNew(['medicine_request_id' => $request->id, 'medicine_listing_id' => $listing->id]);
        $match->fill(['score' => $this->score($request, $listing), 'method' => 'keyword'])->save();

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
