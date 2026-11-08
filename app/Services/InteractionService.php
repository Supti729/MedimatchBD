<?php

namespace App\Services;

use App\Enums\InteractionStatus;
use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Models\Conversation;
use App\Models\Interaction;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\User;
use App\Notifications\InteractionCompleted;
use App\Notifications\ListingRequested;
use App\Notifications\ReservationUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The hand-over workflow:
 *   requester asks  -> interaction "requested", listing "requested", private chat opens
 *   donor reserves  -> interaction "reserved",  listing "reserved"  (needs verification if the medicine requires it)
 *   either confirms -> interaction "completed", listing "completed", linked request "completed"
 *   either cancels  -> interaction "cancelled", listing back to available/requested
 */
class InteractionService
{
    public function __construct(private ListingService $listings, private VerificationService $verification) {}

    public function request(MedicineListing $listing, User $requester, ?MedicineRequest $request = null, ?string $message = null): Interaction
    {
        if ($listing->user_id === $requester->id) {
            throw ValidationException::withMessages(['listing' => 'You cannot request your own listing.']);
        }
        if (! in_array($listing->status, [ListingStatus::Available, ListingStatus::Requested], true) || ! $listing->isPublic()) {
            throw ValidationException::withMessages(['listing' => 'This medicine is not available to request right now.']);
        }
        if ($request && $request->user_id !== $requester->id) {
            $request = null; // never link someone else's request
        }

        $interaction = DB::transaction(function () use ($listing, $requester, $request, $message) {
            $conversation = $this->conversationFor($listing, $requester);

            $interaction = Interaction::where('medicine_listing_id', $listing->id)->where('requester_id', $requester->id)
                ->whereIn('status', [InteractionStatus::Requested, InteractionStatus::Reserved])->first();
            if ($interaction) {
                throw ValidationException::withMessages(['listing' => 'You have already requested this medicine. Continue in Messages.']);
            }

            $interaction = Interaction::where('conversation_id', $conversation->id)->first() ?? new Interaction([
                'medicine_listing_id' => $listing->id, 'donor_id' => $listing->user_id, 'requester_id' => $requester->id, 'conversation_id' => $conversation->id,
            ]);
            $interaction->medicine_request_id = $request?->id;
            $interaction->status = InteractionStatus::Requested;
            $interaction->reserved_at = $interaction->completed_at = $interaction->cancelled_at = null;
            $interaction->save();

            if ($listing->status === ListingStatus::Available) {
                $this->listings->transition($listing, ListingStatus::Requested);
            }
            if ($message) {
                app(MessageService::class)->send($conversation, $requester, $message, notify: false);
            }

            return $interaction;
        });

        $listing->owner->notify(new ListingRequested($interaction->load('requester', 'listing.medicine')));

        return $interaction;
    }

    /** Open (or reuse) the private conversation between a requester and the listing owner. */
    public function conversationFor(MedicineListing $listing, User $requester): Conversation
    {
        return Conversation::firstOrCreate(
            ['medicine_listing_id' => $listing->id, 'requester_id' => $requester->id],
            ['donor_id' => $listing->user_id],
        );
    }

    public function reserve(Interaction $interaction): void
    {
        $listing = $interaction->listing;
        if ($interaction->status !== InteractionStatus::Requested) {
            throw ValidationException::withMessages(['status' => 'Only a pending request can be reserved.']);
        }
        if (! $this->verification->requesterCleared($interaction->requester, $listing->medicine)) {
            throw ValidationException::withMessages(['status' => 'This medicine needs a verified prescription check for the requester before it can be reserved.']);
        }

        DB::transaction(function () use ($interaction, $listing) {
            $this->listings->transition($listing, ListingStatus::Reserved);
            $interaction->forceFill(['status' => InteractionStatus::Reserved, 'reserved_at' => now()])->save();
            if ($interaction->medicineRequest?->status->isOpen()) {
                $interaction->medicineRequest->forceFill(['status' => RequestStatus::Reserved])->save();
            }
        });

        $interaction->requester->notify(new ReservationUpdated($interaction, "{$interaction->donor->name} reserved {$listing->name} for you"));
    }

    public function complete(Interaction $interaction, User $actor): void
    {
        if ($interaction->status !== InteractionStatus::Reserved) {
            throw ValidationException::withMessages(['status' => 'Only a reserved hand-over can be completed.']);
        }

        DB::transaction(function () use ($interaction) {
            $this->listings->transition($interaction->listing, ListingStatus::Completed);
            $interaction->forceFill(['status' => InteractionStatus::Completed, 'completed_at' => now()])->save();
            if ($interaction->medicineRequest) {
                $interaction->medicineRequest->forceFill(['status' => RequestStatus::Completed, 'completed_at' => now()])->save();
            }
            // Anyone else waiting on this listing is told it is gone.
            $this->cancelOpenForListing($interaction->listing, 'This medicine was handed over to another member.', except: $interaction);
        });

        $other = $actor->id === $interaction->donor_id ? $interaction->requester : $interaction->donor;
        $other->notify(new InteractionCompleted($interaction));
    }

    public function cancel(Interaction $interaction, User $actor): void
    {
        if (! $interaction->status->isOpen()) {
            throw ValidationException::withMessages(['status' => 'This hand-over is already closed.']);
        }

        DB::transaction(function () use ($interaction) {
            $wasReserved = $interaction->status === InteractionStatus::Reserved;
            $interaction->forceFill(['status' => InteractionStatus::Cancelled, 'cancelled_at' => now()])->save();
            $this->releaseListing($interaction->listing, $wasReserved);
            if ($interaction->medicineRequest?->status === RequestStatus::Reserved) {
                $interaction->medicineRequest->forceFill(['status' => RequestStatus::Active])->save();
            }
        });

        $other = $actor->id === $interaction->donor_id ? $interaction->requester : $interaction->donor;
        $other->notify(new ReservationUpdated($interaction, "{$actor->name} cancelled the hand-over"));
    }

    /** Used when a listing is cancelled, blocked or completed. */
    public function cancelOpenForListing(MedicineListing $listing, string $reason, ?Interaction $except = null): void
    {
        Interaction::with('requester', 'listing.medicine', 'donor')
            ->where('medicine_listing_id', $listing->id)
            ->whereIn('status', [InteractionStatus::Requested, InteractionStatus::Reserved])
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
            ->get()
            ->each(function (Interaction $i) use ($reason) {
                $i->forceFill(['status' => InteractionStatus::Cancelled, 'cancelled_at' => now()])->save();
                $i->requester->notify(new ReservationUpdated($i, $reason));
            });
    }

    /** After a cancellation, put the listing back to the right state. */
    private function releaseListing(MedicineListing $listing, bool $wasReserved): void
    {
        $listing->refresh();
        $stillRequested = $listing->interactions()->where('status', InteractionStatus::Requested)->exists();
        $target = $stillRequested ? ListingStatus::Requested : ListingStatus::Available;
        if (in_array($listing->status, [ListingStatus::Reserved, ListingStatus::Requested], true) && $listing->status !== $target) {
            $this->listings->transition($listing, $target);
        }
    }
}
