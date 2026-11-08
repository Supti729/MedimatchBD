<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\MedicineEligibility;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ListingStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** All listing state changes go through here, so the status rules live in one place. */
class ListingService
{
    public function __construct(private MatchingService $matching) {}

    /** @param UploadedFile[] $photos */
    public function create(User $owner, array $data, array $photos, bool $asDraft): MedicineListing
    {
        $medicine = Medicine::findOrFail($data['medicine_id']);
        $this->assertListable($medicine);

        return DB::transaction(function () use ($owner, $data, $photos, $asDraft, $medicine) {
            $listing = new MedicineListing($data);
            $listing->user_id = $owner->id;
            $listing->status = ListingStatus::Draft;
            $listing->save();
            $this->storePhotos($listing, $photos);

            if (! $asDraft) {
                $this->submit($listing, $medicine, $owner);
            }

            return $listing;
        });
    }

    /** @param UploadedFile[] $photos */
    public function update(MedicineListing $listing, array $data, array $photos, bool $asDraft): MedicineListing
    {
        $medicine = Medicine::findOrFail($data['medicine_id']);
        $this->assertListable($medicine);

        return DB::transaction(function () use ($listing, $data, $photos, $asDraft, $medicine) {
            $identityChanged = (int) $listing->medicine_id !== (int) $data['medicine_id'] || $listing->expires_on->format('Y-m') !== substr($data['expires_on'], 0, 7);
            $listing->fill($data)->save();
            $this->storePhotos($listing, $photos);

            // Drafts and rejected listings are (re)submitted; live listings go back to review if key details changed.
            if (! $asDraft && in_array($listing->status, [ListingStatus::Draft, ListingStatus::Rejected], true)) {
                $this->submit($listing, $medicine, $listing->owner);
            } elseif ($identityChanged && $listing->status === ListingStatus::Available && $this->needsReview($medicine, $listing->owner)) {
                $this->transition($listing, ListingStatus::PendingReview);
            }

            return $listing;
        });
    }

    /** Owner actions: pause, reactivate, cancel. */
    public function ownerAction(MedicineListing $listing, string $action): void
    {
        $target = match ($action) {
            'pause' => ListingStatus::Paused,
            'reactivate' => ListingStatus::Available,
            'cancel' => ListingStatus::Cancelled,
            default => throw ValidationException::withMessages(['action' => 'Unknown action.']),
        };
        if ($action === 'cancel' && ! $listing->status->ownerCanCancel()) {
            throw ValidationException::withMessages(['action' => 'This listing can no longer be cancelled.']);
        }

        DB::transaction(function () use ($listing, $target) {
            $this->transition($listing, $target);
            if ($target === ListingStatus::Cancelled) {
                app(InteractionService::class)->cancelOpenForListing($listing, 'The donor cancelled this listing.');
            }
        });
        if ($target === ListingStatus::Available) {
            $this->matching->matchListing($listing);
        }
    }

    /** Admin/reviewer moderation: approve, reject or block. */
    public function moderate(MedicineListing $listing, string $decision, string $reason): void
    {
        $target = match ($decision) {
            'approve' => ListingStatus::Available,
            'reject' => ListingStatus::Rejected,
            'block' => ListingStatus::Blocked,
            default => throw ValidationException::withMessages(['decision' => 'Unknown decision.']),
        };

        DB::transaction(function () use ($listing, $target, $reason, $decision) {
            $this->transition($listing, $target);
            $listing->forceFill(['review_note' => $reason, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()])->save();
            if ($target === ListingStatus::Blocked) {
                app(InteractionService::class)->cancelOpenForListing($listing, 'This listing was removed by a moderator.');
            }
            AuditLogger::log("listing.{$decision}", "Listing #{$listing->id} {$listing->name}", $listing, $reason);
        });

        $headline = match ($target) {
            ListingStatus::Available => "{$listing->name} is now live",
            ListingStatus::Rejected => "{$listing->name} was not approved",
            default => "{$listing->name} was removed by a moderator",
        };
        $listing->owner->notify(new ListingStatusChanged($listing, $headline, $target === ListingStatus::Available ? null : $reason));

        if ($target === ListingStatus::Available) {
            $this->matching->matchListing($listing);
        }
    }

    /** Move to a new status if the rules allow it. Refuses anything else. */
    public function transition(MedicineListing $listing, ListingStatus $to): void
    {
        if (! $listing->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => "A {$listing->status->label()} listing cannot become {$to->label()}."]);
        }
        $listing->status = $to;
        if ($to === ListingStatus::Available) {
            $listing->published_at ??= now();
        }
        if ($to === ListingStatus::Completed) {
            $listing->completed_at = now();
        }
        $listing->save();
    }

    public function needsReview(Medicine $medicine, User $owner): bool
    {
        return ($medicine->eligibility === MedicineEligibility::VerificationRequired && Setting::get('review_verification_required'))
            || (Setting::get('review_new_members') && $owner->created_at->gt(now()->subDays(30)) && ! $owner->is_verified);
    }

    private function submit(MedicineListing $listing, Medicine $medicine, User $owner): void
    {
        if ($this->needsReview($medicine, $owner)) {
            $this->transition($listing, ListingStatus::PendingReview);
            $owner->notify(new ListingStatusChanged($listing, "{$listing->name} was submitted for review", 'It will be visible in search once a reviewer approves it.'));
        } else {
            $this->transition($listing, ListingStatus::Available);
            $this->matching->matchListing($listing);
        }
    }

    private function assertListable(Medicine $medicine): void
    {
        if (! $medicine->is_active || $medicine->eligibility === MedicineEligibility::NotAllowed) {
            throw ValidationException::withMessages(['medicine_id' => "{$medicine->brand_name} cannot be listed on Medimatch BD."]);
        }
    }

    /** @param UploadedFile[] $photos */
    private function storePhotos(MedicineListing $listing, array $photos): void
    {
        $position = (int) $listing->images()->max('position');
        foreach (array_slice($photos, 0, max(0, 4 - $listing->images()->count())) as $photo) {
            $path = $photo->store("listings/{$listing->id}", 'public'); // random file name, original name discarded
            $listing->images()->create(['path' => $path, 'position' => ++$position]);
        }
    }

    public function deletePhotos(MedicineListing $listing): void
    {
        foreach ($listing->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        $listing->images()->delete();
    }
}
