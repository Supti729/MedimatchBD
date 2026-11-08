<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\MedicineListing;
use App\Models\Setting;
use App\Notifications\ListingStatusChanged;
use App\Notifications\MatchFound;
use App\Services\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        Storage::fake('public');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'medicine_id' => $this->medicine('Napa 500')->id,
            'quantity' => 20,
            'unit' => 'tablets',
            'expires_on' => now()->addYear()->format('Y-m'),
            'packaging' => 'Sealed, undamaged',
            'type' => 'donation',
            'division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Mirpur',
            'confirm_sealed' => '1', 'confirm_expiry' => '1', 'confirm_free' => '1',
            'intent' => 'submit',
        ], $overrides);
    }

    /** An established member (older than 30 days) so new-member review does not apply. */
    private function donor()
    {
        $user = $this->member();
        $user->forceFill(['created_at' => now()->subDays(60)])->save();

        return $user;
    }

    public function test_eligible_listing_goes_live_with_photos(): void
    {
        $donor = $this->donor();
        $this->actingAs($donor)->post('/my/listings', $this->form(['photos' => [UploadedFile::fake()->image('box.jpg')]]))
            ->assertRedirect(route('listings.index'))->assertSessionHas('success');

        $listing = MedicineListing::firstOrFail();
        $this->assertSame(ListingStatus::Available, $listing->status);
        $this->assertSame($donor->id, $listing->user_id);
        $this->assertSame(1, $listing->images()->count());
        Storage::disk('public')->assertExists($listing->images->first()->path);
        $this->get(route('medicines.show', $listing))->assertOk();
    }

    public function test_verification_required_medicine_waits_for_review(): void
    {
        $this->actingAs($this->donor())->post('/my/listings', $this->form(['medicine_id' => $this->medicine('Comet 500')->id]));
        $listing = MedicineListing::firstOrFail();
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->actingAs($this->member())->get(route('medicines.show', $listing))->assertNotFound(); // others can't see it
        $this->actingAs($listing->owner)->get(route('medicines.show', $listing))->assertOk();        // the owner can
    }

    public function test_new_unverified_members_listings_are_reviewed_first(): void
    {
        $this->actingAs($this->member())->post('/my/listings', $this->form());
        $this->assertSame(ListingStatus::PendingReview, MedicineListing::firstOrFail()->status);

        Setting::put(['review_new_members' => false]);
        $this->actingAs($this->member())->post('/my/listings', $this->form());
        $this->assertSame(ListingStatus::Available, MedicineListing::latest('id')->firstOrFail()->status);
    }

    public function test_not_allowed_medicine_cannot_be_listed(): void
    {
        $this->actingAs($this->donor())->post('/my/listings', $this->form(['medicine_id' => $this->medicine('Insulin pen')->id]))
            ->assertSessionHasErrors('medicine_id');
        $this->assertSame(0, MedicineListing::count());
    }

    public function test_invalid_listing_data_is_rejected(): void
    {
        $donor = $this->donor();
        $this->actingAs($donor)->post('/my/listings', $this->form(['expires_on' => now()->subMonth()->format('Y-m')]))->assertSessionHasErrors('expires_on');
        $this->actingAs($donor)->post('/my/listings', $this->form(['quantity' => 0]))->assertSessionHasErrors('quantity');
        $this->actingAs($donor)->post('/my/listings', $this->form(['packaging' => 'Opened']))->assertSessionHasErrors('packaging');
        $this->actingAs($donor)->post('/my/listings', $this->form(['type' => 'affordable_access']))->assertSessionHasErrors('type');
        $this->actingAs($donor)->post('/my/listings', $this->form(['confirm_free' => null]))->assertSessionHasErrors('confirm_free');
        $this->actingAs($donor)->post('/my/listings', $this->form(['photos' => [UploadedFile::fake()->create('virus.exe', 10)]]))->assertSessionHasErrors('photos.0');
        $this->actingAs($donor)->post('/my/listings', $this->form(['status' => 'completed', 'medicine_id' => 99999]))->assertSessionHasErrors('medicine_id');
        $this->assertSame(0, MedicineListing::count());
    }

    public function test_draft_can_be_saved_without_declarations_and_submitted_later(): void
    {
        $donor = $this->donor();
        $this->actingAs($donor)->post('/my/listings', $this->form(['intent' => 'draft', 'confirm_sealed' => null, 'confirm_expiry' => null, 'confirm_free' => null]));
        $listing = MedicineListing::firstOrFail();
        $this->assertSame(ListingStatus::Draft, $listing->status);

        $this->actingAs($donor)->patch(route('listings.update', $listing), $this->form(['quantity' => 15]))->assertRedirect(route('listings.index'));
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
        $this->assertSame(15, $listing->fresh()->quantity);
    }

    public function test_owner_can_pause_reactivate_and_cancel(): void
    {
        $listing = MedicineListing::factory()->create(['user_id' => $this->donor()->id]);
        $this->actingAs($listing->owner);

        $this->patch(route('listings.status', $listing), ['action' => 'pause']);
        $this->assertSame(ListingStatus::Paused, $listing->fresh()->status);
        $this->actingAs($this->member())->get(route('medicines.show', $listing))->assertNotFound(); // hidden from others while paused
        $this->actingAs($listing->owner)->patch(route('listings.status', $listing), ['action' => 'reactivate']);
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
        $this->patch(route('listings.status', $listing), ['action' => 'cancel']);
        $this->assertSame(ListingStatus::Cancelled, $listing->fresh()->status);

        // Closed listings can't come back or be edited.
        $this->patch(route('listings.status', $listing), ['action' => 'reactivate'])->assertSessionHasErrors('status');
        $this->get(route('listings.edit', $listing))->assertForbidden();
        $this->patch(route('listings.status', $listing), ['action' => 'complete'])->assertSessionHasErrors('action');
        $this->assertSame(ListingStatus::Cancelled, $listing->fresh()->status);
    }

    public function test_status_rules_refuse_illegal_jumps(): void
    {
        $this->assertFalse(ListingStatus::Draft->canTransitionTo(ListingStatus::Completed));
        $this->assertFalse(ListingStatus::Blocked->canTransitionTo(ListingStatus::Available));
        $this->assertFalse(ListingStatus::Completed->canTransitionTo(ListingStatus::Available));
        $this->assertTrue(ListingStatus::Reserved->canTransitionTo(ListingStatus::Completed));
    }

    public function test_admin_approval_publishes_notifies_and_matches_open_requests(): void
    {
        $requester = $this->member();
        app(RequestService::class)->create($requester, ['medicine_id' => $this->medicine('Comet 500')->id, 'medicine_name' => 'x', 'strength' => 'x', 'dosage_form' => 'Tablet', 'quantity' => '10', 'location_id' => $this->location()->id, 'preference' => 'either']);

        $this->actingAs($this->donor())->post('/my/listings', $this->form(['medicine_id' => $this->medicine('Comet 500')->id]));
        $listing = MedicineListing::firstOrFail();
        $this->assertSame(0, $requester->notifications()->count(), 'no match before approval');

        $admin = $this->member();
        $admin->forceFill(['role' => Role::Admin])->save();
        $this->actingAs($admin)->patch(route('admin.listings.decision', $listing), ['decision' => 'approve', 'reason' => 'Looks fine'])->assertSessionHas('success');

        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'listing.approve', 'actor_id' => $admin->id, 'reason' => 'Looks fine']);
        $this->assertTrue($listing->owner->notifications()->where('type', ListingStatusChanged::class)->exists());
        $this->assertTrue($requester->notifications()->where('type', MatchFound::class)->exists());
    }

    public function test_blocked_listing_disappears_from_search(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine('Fexo 120')->id]);
        $this->get('/search?q=fexo')->assertSeeText('Fexo 120');

        $admin = $this->member();
        $admin->forceFill(['role' => Role::Admin])->save();
        $this->actingAs($admin)->patch(route('admin.listings.decision', $listing), ['decision' => 'block', 'reason' => 'Unsafe']);

        $this->assertSame(ListingStatus::Blocked, $listing->fresh()->status);
        $this->get('/search?q=fexo')->assertDontSeeText('Fexo 120');
    }

    public function test_member_can_save_and_unsave_a_listing(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        $member = $this->member();

        $this->actingAs($member)->post(route('saved.store', $listing))->assertSessionHas('success');
        $this->actingAs($member)->get('/saved')->assertSeeText('Napa 500');
        $this->actingAs($member)->delete(route('saved.destroy', $listing));
        $this->assertSame(0, $member->savedListings()->count());

        $this->actingAs($listing->owner)->post(route('saved.store', $listing))->assertForbidden(); // can't save your own
    }

    public function test_my_listings_page_shows_only_my_listings(): void
    {
        $mine = MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Seclo 20')->id]);

        $this->actingAs($mine->owner)->get('/my/listings')->assertOk()->assertSeeText('Napa 500')->assertDontSeeText('Seclo 20');
    }
}
