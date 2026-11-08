<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\RequestStatus;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Notifications\MatchFound;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAndMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'medicine_id' => '',
            'medicine_name' => 'Napa', 'generic_name' => 'Paracetamol', 'strength' => '500mg', 'dosage_form' => 'Tablet',
            'quantity' => '10 tablets', 'preference' => 'either',
            'division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Mirpur',
        ], $overrides);
    }

    private function listing(string $brand, string $district = 'Dhaka', string $upazila = 'Dhanmondi', array $attributes = []): MedicineListing
    {
        return MedicineListing::factory()->create(['medicine_id' => $this->medicine($brand)->id, 'location_id' => $this->location($district, $upazila)->id, ...$attributes]);
    }

    public function test_request_is_created_matched_and_requester_notified(): void
    {
        $napa = $this->listing('Napa 500');
        $member = $this->member();

        $this->actingAs($member)->post('/my/requests', $this->form())->assertSessionHas('success');

        $request = MedicineRequest::firstOrFail();
        $this->assertSame(RequestStatus::MatchFound, $request->status);
        $this->assertSame([$napa->id], $request->matches()->pluck('medicine_listing_id')->all());
        $this->assertTrue($member->notifications()->where('type', MatchFound::class)->exists());
        $this->actingAs($member)->get(route('requests.show', $request))->assertOk()->assertSeeText('Napa 500');
    }

    public function test_catalogue_choice_overrides_free_text(): void
    {
        $this->actingAs($this->member())->post('/my/requests', $this->form(['medicine_id' => $this->medicine('Seclo 20')->id, 'medicine_name' => 'whatever']));
        $request = MedicineRequest::firstOrFail();
        $this->assertSame('Seclo 20', $request->medicine_name);
        $this->assertSame('Capsule', $request->dosage_form);
    }

    public function test_hard_rules_exclude_different_strength_form_own_listings_and_preference(): void
    {
        $member = $this->member();
        $this->listing('Napa Extend');                     // same generic, different strength (665 mg)
        $this->listing('Ace Syrup');                       // same generic, different form
        $this->listing('Napa 500', attributes: ['user_id' => $member->id]); // own listing
        $this->listing('Napa 500', attributes: ['type' => ListingType::AffordableAccess]); // excluded by "donation only"
        $this->listing('Napa 500', attributes: [])->forceFill(['status' => ListingStatus::PendingReview])->save(); // not public

        $this->actingAs($member)->post('/my/requests', $this->form(['preference' => 'donation']));
        $this->assertSame(0, MedicineRequest::firstOrFail()->matches()->count());
    }

    public function test_nearer_listings_rank_higher(): void
    {
        $far = $this->listing('Napa 500', 'Sylhet', 'Sylhet Sadar');
        $near = $this->listing('Napa 500', 'Dhaka', 'Dhanmondi');
        $this->actingAs($this->member())->post('/my/requests', $this->form(['medicine_id' => $this->medicine('Napa 500')->id]));

        $ranked = MedicineRequest::firstOrFail()->matches()->pluck('medicine_listing_id')->all();
        $this->assertSame([$near->id, $far->id], $ranked);
    }

    public function test_new_listing_matches_existing_request(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post('/my/requests', $this->form());
        $request = MedicineRequest::firstOrFail();
        $this->assertSame(RequestStatus::Active, $request->status);

        $listing = $this->listing('Napa 500');
        app(MatchingService::class)->matchListing($listing);

        $this->assertSame(RequestStatus::MatchFound, $request->fresh()->status);
        $this->assertSame(1, $member->notifications()->where('type', MatchFound::class)->count());

        // Matching again never duplicates the notification.
        app(MatchingService::class)->matchListing($listing);
        $this->assertSame(1, $member->notifications()->where('type', MatchFound::class)->count());
    }

    public function test_owner_can_edit_and_cancel_their_request(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post('/my/requests', $this->form());
        $request = MedicineRequest::firstOrFail();

        $this->actingAs($member)->patch(route('requests.update', $request), $this->form(['quantity' => '20 tablets']))->assertRedirect(route('requests.show', $request));
        $this->assertSame('20 tablets', $request->fresh()->quantity);

        $this->actingAs($member)->patch(route('requests.cancel', $request))->assertRedirect(route('requests.index'));
        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);

        // Closed requests can't be edited again.
        $this->actingAs($member)->get(route('requests.edit', $request))->assertForbidden();
    }

    public function test_invalid_request_is_rejected(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post('/my/requests', $this->form(['medicine_name' => '', 'strength' => '', 'dosage_form' => 'Spray', 'preference' => 'free money', 'upazila' => 'Nowhere']))
            ->assertSessionHasErrors(['medicine_name', 'strength', 'dosage_form', 'preference', 'upazila']);
        $this->actingAs($member)->post('/my/requests', $this->form(['medicine_id' => $this->medicine('Insulin pen')->id]))->assertSessionHasErrors('medicine_id');
        $this->assertSame(0, MedicineRequest::count());
    }
}
