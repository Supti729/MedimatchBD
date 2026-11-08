<?php

namespace Tests\Feature;

use App\Enums\InteractionStatus;
use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Interaction;
use App\Models\MedicineListing;
use App\Notifications\InteractionCompleted;
use App\Notifications\ListingRequested;
use App\Notifications\NewMessageReceived;
use App\Notifications\ReservationUpdated;
use App\Services\RequestService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HandoverAndMessagingTest extends TestCase
{
    use RefreshDatabase;

    private MedicineListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        Storage::fake('local');
        $this->listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
    }

    public function test_requesting_opens_a_private_chat_and_notifies_the_donor(): void
    {
        $requester = $this->member();
        $response = $this->actingAs($requester)->post(route('medicines.request', $this->listing), ['message' => 'Could I collect on Saturday?']);

        $interaction = Interaction::firstOrFail();
        $response->assertRedirect(route('messages.show', $interaction->conversation_id));
        $this->assertSame(InteractionStatus::Requested, $interaction->status);
        $this->assertSame(ListingStatus::Requested, $this->listing->fresh()->status);
        $this->assertTrue($this->listing->owner->notifications()->where('type', ListingRequested::class)->exists());
        $this->actingAs($this->listing->owner)->get(route('messages.show', $interaction->conversation_id))->assertSeeText('Could I collect on Saturday?');

        // Asking twice is refused; requesting your own listing is forbidden.
        $this->actingAs($requester)->post(route('medicines.request', $this->listing))->assertSessionHasErrors('listing');
        $this->actingAs($this->listing->owner)->post(route('medicines.request', $this->listing))->assertForbidden();
    }

    public function test_full_handover_reserve_then_complete(): void
    {
        $requester = $this->member();
        $request = app(RequestService::class)->create($requester, ['medicine_id' => $this->medicine('Napa 500')->id, 'medicine_name' => 'x', 'strength' => 'x', 'dosage_form' => 'Tablet', 'quantity' => '10', 'location_id' => $this->location()->id, 'preference' => 'either']);
        $this->actingAs($requester)->post(route('medicines.request', $this->listing), ['medicine_request_id' => $request->id]);
        $interaction = Interaction::firstOrFail();

        $this->actingAs($this->listing->owner)->patch(route('interactions.update', $interaction), ['action' => 'reserve'])->assertSessionHas('success');
        $this->assertSame(ListingStatus::Reserved, $this->listing->fresh()->status);
        $this->assertSame(RequestStatus::Reserved, $request->fresh()->status);
        $this->assertTrue($requester->notifications()->where('type', ReservationUpdated::class)->exists());

        $this->actingAs($requester)->patch(route('interactions.update', $interaction), ['action' => 'complete'])->assertSessionHas('success');
        $this->assertSame(InteractionStatus::Completed, $interaction->fresh()->status);
        $this->assertSame(ListingStatus::Completed, $this->listing->fresh()->status);
        $this->assertSame(RequestStatus::Completed, $request->fresh()->status);
        $this->assertTrue($this->listing->owner->notifications()->where('type', InteractionCompleted::class)->exists());
        $this->assertSame(1, $this->listing->owner->fresh()->completed_count);

        // Completed listings leave search and can't be requested again.
        $this->get('/search?q=napa')->assertDontSeeText('Napa 500');
        $this->actingAs($this->member())->post(route('medicines.request', $this->listing))->assertForbidden();
    }

    public function test_cancelling_a_reservation_releases_the_listing(): void
    {
        $requester = $this->member();
        $this->actingAs($requester)->post(route('medicines.request', $this->listing));
        $interaction = Interaction::firstOrFail();
        $this->actingAs($this->listing->owner)->patch(route('interactions.update', $interaction), ['action' => 'reserve']);

        $this->actingAs($requester)->patch(route('interactions.update', $interaction), ['action' => 'cancel'])->assertSessionHas('success');
        $this->assertSame(InteractionStatus::Cancelled, $interaction->fresh()->status);
        $this->assertSame(ListingStatus::Available, $this->listing->fresh()->status);
    }

    public function test_verification_required_medicine_needs_a_verified_prescription_before_reserving(): void
    {
        $comet = MedicineListing::factory()->create(['medicine_id' => $this->medicine('Comet 500')->id]);
        $requester = $this->member();
        $request = app(RequestService::class)->create($requester, ['medicine_id' => $this->medicine('Comet 500')->id, 'medicine_name' => 'x', 'strength' => 'x', 'dosage_form' => 'Tablet', 'quantity' => '30', 'location_id' => $this->location()->id, 'preference' => 'either']);
        $this->actingAs($requester)->post(route('medicines.request', $comet), ['medicine_request_id' => $request->id]);
        $interaction = Interaction::firstOrFail();

        $this->actingAs($comet->owner)->patch(route('interactions.update', $interaction), ['action' => 'reserve'])->assertSessionHasErrors('status');
        $this->assertSame(ListingStatus::Requested, $comet->fresh()->status);

        $submission = app(VerificationService::class)->submit($requester, 'prescription', UploadedFile::fake()->image('rx.png'), $request);
        $reviewer = $this->member();
        $reviewer->forceFill(['role' => Role::Reviewer])->save();
        $this->actingAs($reviewer);
        app(VerificationService::class)->decide($submission, $reviewer, VerificationStatus::Verified, 'Valid prescription.');

        // The donor only learns that the check passed — never sees the document.
        $this->actingAs($comet->owner)->get(route('messages.show', $interaction->conversation_id))->assertDontSee('rx.png');
        $this->actingAs($comet->owner)->patch(route('interactions.update', $interaction), ['action' => 'reserve'])->assertSessionHas('success');
        $this->assertSame(ListingStatus::Reserved, $comet->fresh()->status);
    }

    public function test_participants_can_message_and_unread_counts_update(): void
    {
        $requester = $this->member();
        $this->actingAs($requester)->post(route('medicines.request', $this->listing));
        $conversation = Interaction::firstOrFail()->conversation;
        $donor = $this->listing->owner;

        $this->actingAs($donor)->post(route('messages.send', $conversation), ['body' => 'Yes, still available.'])->assertRedirect(route('messages.show', $conversation));
        $this->actingAs($donor)->post(route('messages.send', $conversation), ['body' => 'Saturday works.']);
        $this->assertSame(2, $conversation->unreadCountFor($requester));
        $this->assertSame(1, $requester->notifications()->where('type', NewMessageReceived::class)->count(), 'one unread notification per conversation');

        $this->actingAs($requester)->get('/dashboard')->assertSeeText('Messages');
        $this->actingAs($requester)->get(route('messages.show', $conversation))->assertSeeText('Saturday works.');
        $this->assertSame(0, $conversation->fresh()->unreadCountFor($requester));
    }

    public function test_messages_with_contact_details_are_blocked(): void
    {
        $requester = $this->member();
        $this->actingAs($requester)->post(route('medicines.request', $this->listing));
        $conversation = Interaction::firstOrFail()->conversation;

        foreach (['Call me on 01712345678', 'my number +880 1712-345678', 'email me at someone@example.com'] as $body) {
            $this->actingAs($requester)->post(route('messages.send', $conversation), ['body' => $body])->assertSessionHasErrors('body');
        }
        $this->actingAs($requester)->post(route('messages.send', $conversation), ['body' => str_repeat('a', 1001)])->assertSessionHasErrors('body');
        $this->assertSame(0, $conversation->messages()->count());
    }

    public function test_cancelled_listing_cancels_open_requests(): void
    {
        $requester = $this->member();
        $this->actingAs($requester)->post(route('medicines.request', $this->listing));
        $this->actingAs($this->listing->owner)->patch(route('listings.status', $this->listing), ['action' => 'cancel']);

        $this->assertSame(InteractionStatus::Cancelled, Interaction::firstOrFail()->status);
        $this->assertTrue($requester->notifications()->where('type', ReservationUpdated::class)->exists());
    }
}
