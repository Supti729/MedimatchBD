<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\MedicineListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public static function publicPages(): array
    {
        return array_map(fn ($u) => [$u], ['/', '/how-it-works', '/safety', '/about', '/contact', '/privacy', '/terms', '/medicines', '/medicines?view=list', '/search?q=napa', '/login', '/register', '/forgot-password', '/reset-password/token', '/admin/login']);
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_only_public_listings_can_be_opened(): void
    {
        $live = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        $this->get(route('medicines.show', $live))->assertOk()->assertSeeText('Napa 500');

        foreach ([ListingStatus::Draft, ListingStatus::PendingReview, ListingStatus::Blocked, ListingStatus::Paused, ListingStatus::Cancelled] as $status) {
            $hidden = MedicineListing::factory()->status($status)->create();
            $this->get(route('medicines.show', $hidden))->assertNotFound();
        }
    }

    public function test_expired_listings_and_listings_of_suspended_members_are_hidden(): void
    {
        $expired = MedicineListing::factory()->create(['expires_on' => now()->subMonth()]);
        $this->get(route('medicines.show', $expired))->assertNotFound();

        $listing = MedicineListing::factory()->create();
        $listing->owner->forceFill(['status' => AccountStatus::Suspended])->save();
        $this->get(route('medicines.show', $listing))->assertNotFound();
    }

    public function test_search_matches_brand_generic_alias_and_strength(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id, 'location_id' => $this->location()->id]);
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Seclo 20')->id, 'location_id' => $this->location('Sylhet', 'Sylhet Sadar')->id]);

        $this->get('/search?q=napa')->assertSeeText('Napa 500')->assertDontSeeText('Seclo 20');
        $this->get('/search?q=paracetamol 500mg')->assertSeeText('Napa 500')->assertDontSeeText('Seclo 20');
        $this->get('/search?q=acetaminophen')->assertSeeText('Napa 500');   // catalogue alias
        $this->get('/search?q=নাপা')->assertSeeText('Napa 500');            // Bangla alias
        $this->get('/medicines?division=Sylhet')->assertSeeText('Seclo 20')->assertDontSeeText('Napa 500');
        $this->get('/medicines?division=Dhaka&district=Dhaka&upazila=Mirpur')->assertSeeText('Napa 500');
        $this->get('/medicines?form=Capsule')->assertSeeText('Seclo 20')->assertDontSeeText('Napa 500');
        $this->get('/medicines?division=Atlantis')->assertOk()->assertSeeText('Napa 500'); // unknown values ignored
        $this->get('/search?q=zzzz-not-real')->assertSee('No eligible medicine matches your search');
    }

    public function test_search_never_returns_blocked_listings(): void
    {
        MedicineListing::factory()->status(ListingStatus::Blocked)->create(['medicine_id' => $this->medicine('Fexo 120')->id]);
        $this->get('/search?q=fexo')->assertSee('No eligible medicine matches your search');
    }

    public function test_public_profile_shows_minimum_information(): void
    {
        $member = $this->member(attributes: ['email' => 'private@example.com', 'phone' => '+8801712345678']);
        $this->get(route('members.show', $member))->assertOk()->assertSee($member->name)
            ->assertDontSee('private@example.com')->assertDontSee('1712345678');
    }

    public function test_staff_and_suspended_profiles_are_not_public(): void
    {
        $admin = $this->member();
        $admin->forceFill(['role' => Role::Admin])->save();
        $this->get(route('members.show', $admin))->assertNotFound();

        $suspended = $this->member();
        $suspended->forceFill(['status' => AccountStatus::Suspended])->save();
        $this->get(route('members.show', $suspended))->assertNotFound();
    }

    public function test_unknown_page_shows_branded_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_contact_form_validates_and_sends(): void
    {
        $this->post('/contact', [])->assertSessionHasErrors(['name', 'email', 'topic', 'message']);
        $this->post('/contact', ['name' => 'Asha', 'email' => 'asha@example.com', 'topic' => 'general', 'message' => 'Hello, I have a question about the project.'])
            ->assertSessionHas('success');
    }
}
