<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ListingStatus;
use App\Enums\ReportStatus;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\Models\VerificationSubmission;
use App\Notifications\ReportStatusUpdated;
use App\Notifications\VerificationStatusChanged;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class VerificationAndModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        Storage::fake('local');
    }

    private function staff(Role $role): User
    {
        $user = $this->member();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    /* ------------------------------------------------------------ Verification */

    public function test_member_uploads_a_document_privately(): void
    {
        $member = $this->member();
        $request = MedicineRequest::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)->post('/verification', [
            'kind' => 'prescription', 'request_id' => $request->id, 'document' => UploadedFile::fake()->image('rx.jpg'), 'consent' => '1',
        ])->assertRedirect(route('verification.index'));

        $submission = VerificationSubmission::firstOrFail();
        $this->assertSame(VerificationStatus::Submitted, $submission->status);
        Storage::disk('local')->assertExists($submission->document_path);   // private disk
        $this->assertStringStartsWith("verification/{$member->id}/", $submission->document_path);
        $this->assertSame(RequestStatus::VerificationPending, $request->fresh()->status);
        $this->actingAs($member)->get('/verification')->assertOk()->assertSeeText('Prescription check');
    }

    public function test_invalid_uploads_are_rejected(): void
    {
        $member = $this->member();
        $someoneElsesRequest = MedicineRequest::factory()->create();

        $this->actingAs($member)->post('/verification', ['kind' => 'identity', 'document' => UploadedFile::fake()->create('doc.exe', 10), 'consent' => '1'])->assertSessionHasErrors('document');
        $this->actingAs($member)->post('/verification', ['kind' => 'identity', 'document' => UploadedFile::fake()->image('big.png')->size(6000), 'consent' => '1'])->assertSessionHasErrors('document');
        $this->actingAs($member)->post('/verification', ['kind' => 'identity', 'document' => UploadedFile::fake()->image('id.png')])->assertSessionHasErrors('consent');
        $this->actingAs($member)->post('/verification', ['kind' => 'prescription', 'request_id' => $someoneElsesRequest->id, 'document' => UploadedFile::fake()->image('rx.png'), 'consent' => '1'])->assertSessionHasErrors('request_id');
        $this->assertSame(0, VerificationSubmission::count());
    }

    public function test_reviewer_decides_with_history_audit_and_notification(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post('/verification', ['kind' => 'identity', 'document' => UploadedFile::fake()->image('id.png'), 'consent' => '1']);
        $submission = VerificationSubmission::firstOrFail();
        $reviewer = $this->staff(Role::Reviewer);

        $this->actingAs($reviewer)->get(route('admin.verifications.show', $submission))->assertOk();
        $this->assertSame(VerificationStatus::UnderReview, $submission->fresh()->status);

        $this->actingAs($reviewer)->patch(route('admin.verifications.decision', $submission), ['decision' => 'verified', 'note' => 'Name matches.'])
            ->assertRedirect(route('admin.verifications.index'));

        $submission->refresh();
        $this->assertSame(VerificationStatus::Verified, $submission->status);
        $this->assertSame(['submitted', 'under_review', 'verified'], $submission->reviews->map(fn ($r) => $r->to_status->value)->all());
        $this->assertNotNull($member->fresh()->identity_verified_at);
        $this->assertTrue($member->notifications()->where('type', VerificationStatusChanged::class)->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'verification.verified', 'actor_id' => $reviewer->id, 'reason' => 'Name matches.']);

        // A decided submission cannot be decided again.
        $this->actingAs($reviewer)->patch(route('admin.verifications.decision', $submission), ['decision' => 'rejected', 'note' => 'x'])->assertForbidden();
    }

    /* ---------------------------------------------------------------- Reports */

    public function test_member_reports_a_listing_and_admin_blocks_it(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        $reporter = $this->member();

        $this->actingAs($reporter)->post('/reports', ['listing_id' => $listing->id, 'category' => 'Restricted or prohibited medicine', 'details' => 'Looks restricted'])->assertSessionHas('success');
        $report = Report::firstOrFail();
        $this->assertSame($listing->user_id, $report->reported_user_id);
        $this->assertSame(ReportStatus::Open, $report->status);

        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->get(route('admin.reports.show', $report))->assertOk();
        $this->actingAs($admin)->patch(route('admin.reports.decision', $report), ['decision' => 'block_listing', 'note' => 'Confirmed restricted.'])->assertSessionHas('success');

        $this->assertSame(ReportStatus::Resolved, $report->fresh()->status);
        $this->assertSame(ListingStatus::Blocked, $listing->fresh()->status);
        $this->assertTrue($reporter->notifications()->where('type', ReportStatusUpdated::class)->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.block_listing']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'listing.block']);
    }

    public function test_report_can_suspend_a_member_which_ends_their_sessions(): void
    {
        $offender = $this->member();
        $this->actingAs($this->member())->post('/reports', ['user_id' => $offender->id, 'category' => 'Harassment or abuse']);
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $offender->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->staff(Role::Admin))->patch(route('admin.reports.decision', Report::firstOrFail()), ['decision' => 'suspend', 'note' => 'Repeated abuse.']);

        $this->assertSame(AccountStatus::Suspended, $offender->fresh()->status);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $offender->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended']);
    }

    public function test_members_cannot_report_themselves_or_unseen_conversations(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post('/reports', ['user_id' => $member->id, 'category' => 'Other'])->assertSessionHasErrors('category');
        $this->actingAs($member)->post('/reports', ['listing_id' => 1, 'category' => 'Not a category'])->assertSessionHasErrors('category');
        $this->assertSame(0, Report::count());
    }

    /* ------------------------------------------------------------ Admin tools */

    public function test_admin_can_suspend_and_restore_with_reasons_logged(): void
    {
        $admin = $this->staff(Role::Admin);
        $member = $this->member();

        $this->actingAs($admin)->patch(route('admin.users.status', $member), ['status' => 'suspended', 'reason' => 'Spam'])->assertSessionHas('success');
        $this->assertSame(AccountStatus::Suspended, $member->fresh()->status);
        $this->actingAs($admin)->patch(route('admin.users.status', $member), ['status' => 'active', 'reason' => 'Appeal accepted'])->assertSessionHas('success');
        $this->assertSame(AccountStatus::Active, $member->fresh()->status);

        $this->assertSame(['user.suspended', 'user.restored'], AuditLog::where('subject_id', $member->id)->where('action', 'like', 'user.%')->where('action', '!=', 'user.viewed')->orderBy('id')->pluck('action')->all());
        $this->actingAs($admin)->patch(route('admin.users.status', $member), ['status' => 'suspended'])->assertSessionHasErrors('reason');
    }

    public function test_admin_can_promote_a_member_to_reviewer(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff(Role::Admin))->patch(route('admin.users.role', $member), ['role' => 'reviewer', 'reason' => 'Licensed pharmacist volunteer']);
        $this->assertSame(Role::Reviewer, $member->fresh()->role);
        $this->actingAs($member->fresh())->get('/admin/verifications')->assertOk();
    }

    public function test_catalogue_entries_can_be_added_and_rules_changed(): void
    {
        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->post(route('admin.medicines.store'), ['brand_name' => 'Histacin', 'generic_name' => 'Chlorpheniramine', 'strength' => '4 mg', 'dosage_form' => 'Tablet', 'eligibility' => 'eligible', 'aliases' => 'Histacin, Chlorphenamine'])
            ->assertSessionHas('success');
        $medicine = Medicine::where('brand_name', 'Histacin')->firstOrFail();
        $this->assertSame(2, $medicine->aliases()->count());

        $this->actingAs($admin)->post(route('admin.medicines.store'), ['brand_name' => 'Histacin', 'generic_name' => 'x', 'strength' => '4 mg', 'dosage_form' => 'Tablet', 'eligibility' => 'eligible'])->assertSessionHasErrors('brand_name');

        $this->actingAs($admin)->patch(route('admin.medicines.update', $medicine), ['eligibility' => 'not_allowed', 'is_active' => '1', 'aliases' => 'Histacin', 'reason' => 'Policy change'])->assertSessionHas('success');
        $this->assertSame('not_allowed', $medicine->fresh()->eligibility->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalogue.updated', 'reason' => 'Policy change']);
    }

    public function test_settings_are_saved_and_change_behaviour(): void
    {
        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->patch(route('admin.settings.update'), ['allow_affordable_access' => '1', 'review_verification_required' => '1', 'min_expiry_months' => 3, 'request_expiry_days' => 14])->assertSessionHas('success');

        $this->assertTrue(Setting::get('allow_affordable_access'));
        $this->assertFalse(Setting::get('review_new_members'));
        $this->assertSame(3, Setting::get('min_expiry_months'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_audit_log_is_append_only(): void
    {
        $this->actingAs($this->staff(Role::Admin));
        $log = AuditLogger::log('test.action', 'Something happened');

        $this->expectException(LogicException::class);
        $log->update(['description' => 'Changed']);
    }
}
