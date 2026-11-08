<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\User;
use App\Services\InteractionService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function staff(Role $role): User
    {
        $user = $this->member();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    public static function adminOnlyPages(): array
    {
        return array_map(fn ($u) => [$u], ['/admin/users', '/admin/medicines', '/admin/listings', '/admin/requests', '/admin/reports', '/admin/interactions', '/admin/partners', '/admin/announcements', '/admin/audit-logs', '/admin/settings']);
    }

    #[DataProvider('adminOnlyPages')]
    public function test_members_and_reviewers_cannot_open_admin_only_pages(string $url): void
    {
        $this->actingAs($this->member())->get($url)->assertForbidden();
        $this->actingAs($this->staff(Role::Reviewer))->get($url)->assertForbidden();
        $this->actingAs($this->staff(Role::Admin))->get($url)->assertOk();
    }

    public function test_members_cannot_open_the_admin_area_at_all(): void
    {
        $this->actingAs($this->member())->get('/admin')->assertForbidden();
        $this->actingAs($this->member())->get('/admin/verifications')->assertForbidden();
    }

    public function test_member_cannot_change_admin_data(): void
    {
        $member = $this->member();
        $target = $this->member();
        $listing = MedicineListing::factory()->status(ListingStatus::PendingReview)->create();

        $this->actingAs($member)->patch(route('admin.users.status', $target), ['status' => 'suspended', 'reason' => 'x'])->assertForbidden();
        $this->actingAs($member)->patch(route('admin.users.role', $member), ['role' => 'admin', 'reason' => 'x'])->assertForbidden();
        $this->actingAs($member)->patch(route('admin.listings.decision', $listing), ['decision' => 'approve', 'reason' => 'x'])->assertForbidden();

        $this->assertSame(AccountStatus::Active, $target->fresh()->status);
        $this->assertSame(Role::User, $member->fresh()->role);
        $this->assertSame(ListingStatus::PendingReview, $listing->fresh()->status);
    }

    public function test_member_cannot_edit_or_change_another_members_listing(): void
    {
        $listing = MedicineListing::factory()->create();
        $intruder = $this->member();

        $this->actingAs($intruder)->get(route('listings.edit', $listing))->assertForbidden();
        $this->actingAs($intruder)->patch(route('listings.update', $listing), ['medicine_id' => $listing->medicine_id])->assertForbidden();
        $this->actingAs($intruder)->patch(route('listings.status', $listing), ['action' => 'cancel'])->assertForbidden();
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
    }

    public function test_member_cannot_view_or_change_another_members_request(): void
    {
        $request = MedicineRequest::factory()->create();
        $intruder = $this->member();

        $this->actingAs($intruder)->get(route('requests.show', $request))->assertForbidden();
        $this->actingAs($intruder)->get(route('requests.edit', $request))->assertForbidden();
        $this->actingAs($intruder)->patch(route('requests.cancel', $request))->assertForbidden();
    }

    public function test_only_participants_can_read_a_conversation(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        $requester = $this->member();
        $interaction = app(InteractionService::class)->request($listing, $requester, null, 'Hello');

        $this->actingAs($requester)->get(route('messages.show', $interaction->conversation_id))->assertOk()->assertSee('Hello');
        $this->actingAs($listing->owner)->get(route('messages.show', $interaction->conversation_id))->assertOk();

        $outsider = $this->member();
        $this->actingAs($outsider)->get(route('messages.show', $interaction->conversation_id))->assertForbidden();
        $this->actingAs($outsider)->post(route('messages.send', $interaction->conversation_id), ['body' => 'hi'])->assertForbidden();
        $this->actingAs($outsider)->patch(route('interactions.update', $interaction), ['action' => 'reserve'])->assertForbidden();
    }

    public function test_only_the_donor_can_reserve(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        $requester = $this->member();
        $interaction = app(InteractionService::class)->request($listing, $requester);

        $this->actingAs($requester)->patch(route('interactions.update', $interaction), ['action' => 'reserve'])->assertForbidden();
        $this->actingAs($listing->owner)->patch(route('interactions.update', $interaction), ['action' => 'reserve'])->assertSessionHas('success');
    }

    public function test_private_documents_are_only_visible_to_authorised_reviewers(): void
    {
        $owner = $this->member();
        $submission = app(VerificationService::class)->submit($owner, 'identity', UploadedFile::fake()->image('id.png'));
        $url = route('admin.verifications.document', $submission);

        $this->actingAs($owner)->get($url)->assertForbidden();          // not even the owner, from the admin route
        $this->actingAs($this->member())->get($url)->assertForbidden();  // other members / donors
        $this->actingAs($this->staff(Role::Reviewer))->get($url)->assertOk();
        $this->actingAs($this->staff(Role::Admin))->get($url)->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'verification.document_viewed']);

        // There is no public URL for the file.
        $this->get('/storage/'.$submission->document_path)->assertNotFound();
    }

    public function test_reviewer_cannot_review_their_own_submission(): void
    {
        $reviewer = $this->staff(Role::Reviewer);
        $submission = app(VerificationService::class)->submit($reviewer, 'identity', UploadedFile::fake()->image('id.png'));

        $this->actingAs($reviewer)->get(route('admin.verifications.show', $submission))->assertForbidden();
        $this->actingAs($reviewer)->patch(route('admin.verifications.decision', $submission), ['decision' => 'verified', 'note' => 'ok'])->assertForbidden();
    }

    public function test_admin_cannot_change_their_own_status_or_role(): void
    {
        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->patch(route('admin.users.status', $admin), ['status' => 'suspended', 'reason' => 'test'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->patch(route('admin.users.role', $admin), ['role' => 'user', 'reason' => 'test'])->assertSessionHasErrors('status');
        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_profile_update_cannot_change_role(): void
    {
        $member = $this->member();
        $this->actingAs($member)->patch('/profile', [
            'name' => 'New Name', 'email' => $member->email, 'phone' => '0'.substr($member->phone, 4),
            'division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Mirpur', 'role' => 'admin', 'status' => 'active',
        ])->assertRedirect(route('profile.show'));
        $this->assertSame('New Name', $member->fresh()->name);
        $this->assertSame(Role::User, $member->fresh()->role);
    }

    public function test_others_notifications_cannot_be_opened(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        app(InteractionService::class)->request($listing, $this->member());
        $notification = $listing->owner->notifications()->firstOrFail();

        $this->actingAs($this->member())->get(route('notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($this->member())->patch(route('notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
