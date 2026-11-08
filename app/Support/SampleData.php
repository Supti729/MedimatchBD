<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\InteractionStatus;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\MedicineEligibility;
use App\Enums\PartnerStatus;
use App\Enums\ReportStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PART 1 (frontend preview) ONLY.
 *
 * Fictional sample records used to render every page before the database exists.
 * Records are plain objects shaped like the Eloquent models planned for Part 2
 * (e.g. $listing->owner->name, $listing->status->label()), so the Blade views can
 * keep working when controllers switch from SampleData to real queries.
 *
 * Nothing here is real platform data. People and organisations are invented.
 */
class SampleData
{
    /** The member shown as "logged in" on preview pages. */
    public const CURRENT_USER_ID = 1;

    private static array $cache = [];

    private static function memo(string $key, callable $build): Collection
    {
        return self::$cache[$key] ??= $build();
    }

    private static function days(int $days, int $hours = 0): Carbon
    {
        return Carbon::now()->subDays($days)->subHours($hours);
    }

    /* ------------------------------------------------------------------ Users */

    public static function users(): Collection
    {
        return self::memo('users', fn () => collect([
            [1, 'Nadia Rahman', 'Dhaka', 'Dhaka', 'Mirpur', 'user', true, 4, 120],
            [2, 'Rahim Uddin', 'Dhaka', 'Dhaka', 'Dhanmondi', 'user', true, 3, 300],
            [3, 'Farhana Akter', 'Sylhet', 'Sylhet', 'Sylhet Sadar', 'user', true, 1, 210],
            [4, 'Tanvir Hasan', 'Chattogram', 'Chattogram', 'Kotwali', 'user', false, 0, 45],
            [5, 'Mithila Das', 'Rajshahi', 'Rajshahi', 'Boalia', 'user', true, 6, 400],
            [6, 'Arif Chowdhury', 'Khulna', 'Khulna', 'Khulna Sadar', 'user', false, 2, 90],
            [7, 'Sumaiya Islam', 'Dhaka', 'Gazipur', 'Gazipur Sadar', 'reviewer', true, 0, 380],
            [8, 'Kamal Hossain', 'Dhaka', 'Dhaka', 'Gulshan', 'admin', true, 0, 500],
            [9, 'Rupa Begum', 'Mymensingh', 'Mymensingh', 'Trishal', 'user', false, 0, 12],
        ])->map(fn ($u) => (object) [
            'id' => $u[0],
            'name' => $u[1],
            'division' => $u[2],
            'district' => $u[3],
            'upazila' => $u[4],
            'role' => $u[5],
            'is_verified' => $u[6],
            'completed_count' => $u[7],
            'joined_at' => self::days($u[8]),
            'last_active_at' => self::days($u[0] % 4, $u[0]),
            'status' => match ($u[0]) { 4 => AccountStatus::Suspended, 9 => AccountStatus::Pending, default => AccountStatus::Active },
            // Contact details are private: only the owner and administrators ever see them.
            'email' => strtolower(strtok($u[1], ' ')).$u[0].'@example.com',
            'phone' => sprintf('+880 17%02d-•••%03d', $u[0] * 7 % 100, $u[0] * 137 % 1000),
        ])->keyBy('id'));
    }

    public static function user(int $id): ?object
    {
        return self::users()->get($id);
    }

    public static function currentUser(): object
    {
        return self::user(self::CURRENT_USER_ID);
    }

    /* -------------------------------------------------------- Medicine catalogue */

    public static function catalogue(): Collection
    {
        return self::memo('catalogue', fn () => collect([
            [1, 'Napa 500', 'Paracetamol', '500 mg', 'Tablet', ['Napa', 'Paracetamol 500', 'Acetaminophen'], MedicineEligibility::Eligible],
            [2, 'Napa Extend', 'Paracetamol', '665 mg', 'Tablet', ['Napa ER', 'Paracetamol extended release'], MedicineEligibility::Eligible],
            [3, 'Ace Syrup', 'Paracetamol', '120 mg/5 ml', 'Syrup', ['Ace', 'Paracetamol suspension'], MedicineEligibility::Eligible],
            [4, 'Seclo 20', 'Omeprazole', '20 mg', 'Capsule', ['Seclo', 'Omeprazole 20'], MedicineEligibility::Eligible],
            [5, 'Maxpro 20', 'Esomeprazole', '20 mg', 'Tablet', ['Maxpro', 'Esomeprazole 20'], MedicineEligibility::Eligible],
            [6, 'Fexo 120', 'Fexofenadine', '120 mg', 'Tablet', ['Fexo', 'Fexofenadine HCl'], MedicineEligibility::Eligible],
            [7, 'Monas 10', 'Montelukast', '10 mg', 'Tablet', ['Monas', 'Montelukast sodium'], MedicineEligibility::VerificationRequired],
            [8, 'Comet 500', 'Metformin', '500 mg', 'Tablet', ['Comet', 'Metformin HCl'], MedicineEligibility::VerificationRequired],
            [9, 'Insulin pen (sample)', 'Insulin', '100 IU/ml', 'Injection', ['Insulin'], MedicineEligibility::NotAllowed],
            [10, 'Neotack 150', 'Ranitidine', '150 mg', 'Tablet', ['Ranitidine'], MedicineEligibility::NotAllowed],
        ])->map(fn ($m) => (object) [
            'id' => $m[0], 'brand' => $m[1], 'generic' => $m[2], 'strength' => $m[3], 'form' => $m[4],
            'aliases' => $m[5], 'eligibility' => $m[6],
        ])->keyBy('id'));
    }

    public static function dosageForms(): array
    {
        return ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Drops', 'Ointment', 'Cream', 'Inhaler', 'Injection'];
    }

    /* --------------------------------------------------------------- Listings */

    public static function listings(): Collection
    {
        return self::memo('listings', function () {
            $rows = [
                // id, catalogue id, owner, qty, note, expiry months, packaging, division, district, upazila, type, status, created days ago
                [1, 1, 2, '20 tablets', '2 strips', 16, 'Sealed, undamaged', 'Dhaka', 'Dhaka', 'Dhanmondi', ListingType::Donation, ListingStatus::Available, 1],
                [2, 4, 3, '14 capsules', '1 strip', 11, 'Sealed, undamaged', 'Sylhet', 'Sylhet', 'Sylhet Sadar', ListingType::AffordableAccess, ListingStatus::Reserved, 3],
                [3, 3, 4, '1 bottle', '60 ml, unopened', 9, 'Sealed bottle', 'Chattogram', 'Chattogram', 'Kotwali', ListingType::Donation, ListingStatus::Available, 4],
                [4, 2, 1, '30 tablets', '3 strips', 14, 'Sealed, undamaged', 'Dhaka', 'Dhaka', 'Mirpur', ListingType::Donation, ListingStatus::Available, 5],
                [5, 6, 5, '10 tablets', '1 strip', 20, 'Sealed, undamaged', 'Rajshahi', 'Rajshahi', 'Boalia', ListingType::AffordableAccess, ListingStatus::Available, 6],
                [6, 5, 6, '28 tablets', '2 strips', 12, 'Outer box opened, strips sealed', 'Khulna', 'Khulna', 'Khulna Sadar', ListingType::Donation, ListingStatus::Requested, 7],
                [7, 1, 5, '10 tablets', '1 strip', 8, 'Sealed, undamaged', 'Rajshahi', 'Rajshahi', 'Paba', ListingType::Donation, ListingStatus::Available, 8],
                [8, 7, 1, '15 tablets', '1 strip', 10, 'Sealed, undamaged', 'Dhaka', 'Dhaka', 'Mirpur', ListingType::Donation, ListingStatus::PendingReview, 2],
                [9, 8, 3, '30 tablets', '3 strips', 13, 'Sealed, undamaged', 'Sylhet', 'Sylhet', 'Golapganj', ListingType::Donation, ListingStatus::Available, 10],
                [10, 4, 7, '7 capsules', 'Half strip', 7, 'Sealed, undamaged', 'Dhaka', 'Gazipur', 'Kaliakair', ListingType::Donation, ListingStatus::Available, 12],
                [11, 6, 1, '20 tablets', '2 strips', 18, 'Sealed, undamaged', 'Dhaka', 'Dhaka', 'Mirpur', ListingType::Donation, ListingStatus::Completed, 40],
                [12, 3, 2, '1 bottle', '100 ml, unopened', 6, 'Sealed bottle', 'Dhaka', 'Dhaka', 'Dhanmondi', ListingType::Donation, ListingStatus::Available, 14],
                [13, 10, 4, '20 tablets', '2 strips', 5, 'Sealed', 'Chattogram', 'Chattogram', 'Panchlaish', ListingType::Donation, ListingStatus::Blocked, 9],
                [14, 5, 1, '14 tablets', '1 strip', 9, 'Sealed, undamaged', 'Dhaka', 'Dhaka', 'Mirpur', ListingType::Donation, ListingStatus::Draft, 0],
            ];

            return collect($rows)->map(function ($r) {
                $medicine = self::catalogue()->get($r[1]);

                return (object) [
                    'id' => $r[0],
                    'medicine' => $medicine,
                    'name' => $medicine->brand,
                    'generic' => $medicine->generic,
                    'strength' => $medicine->strength,
                    'form' => $medicine->form,
                    'owner' => self::user($r[2]),
                    'quantity' => $r[3],
                    'quantity_note' => $r[4],
                    'expires_at' => Carbon::now()->startOfMonth()->addMonths($r[5]),
                    'packaging' => $r[6],
                    'division' => $r[7],
                    'district' => $r[8],
                    'upazila' => $r[9],
                    'type' => $r[10],
                    'status' => $r[11],
                    'eligibility' => $medicine->eligibility,
                    'catalogue_verified' => true,
                    'description' => 'Left over after a completed course. Stored at room temperature away from sunlight.',
                    'created_at' => self::days($r[12], 3),
                    'updated_at' => self::days(max(0, $r[12] - 1)),
                ];
            })->keyBy('id');
        });
    }

    public static function listing(int $id): ?object
    {
        return self::listings()->get($id);
    }

    /** Statuses that may appear in public search. Blocked, draft or unreviewed listings never do. */
    public static function publicStatuses(): array
    {
        return [ListingStatus::Available, ListingStatus::Requested, ListingStatus::Reserved];
    }

    public static function publicListings(): Collection
    {
        return self::listings()->filter(fn ($l) => in_array($l->status, self::publicStatuses(), true));
    }

    /* --------------------------------------------------------------- Requests */

    public static function requests(): Collection
    {
        return self::memo('requests', function () {
            $rows = [
                // id, catalogue id, owner, qty, division, district, upazila, preference, status, created days ago, matches [listing id => sample relevance]
                [1, 1, 1, '10 tablets', 'Dhaka', 'Dhaka', 'Mirpur', 'either', RequestStatus::MatchFound, 2, [1 => 94, 7 => 88, 4 => 71]],
                [2, 6, 1, '10 tablets', 'Dhaka', 'Dhaka', 'Mirpur', 'donation', RequestStatus::Active, 6, []],
                [3, 8, 1, '30 tablets', 'Dhaka', 'Dhaka', 'Mirpur', 'donation', RequestStatus::VerificationPending, 9, [9 => 90]],
                [4, 4, 1, '14 capsules', 'Dhaka', 'Dhaka', 'Mirpur', 'either', RequestStatus::Completed, 35, []],
                [5, 3, 6, '1 bottle', 'Khulna', 'Khulna', 'Sonadanga', 'donation', RequestStatus::Matching, 1, [3 => 82, 12 => 80]],
                [6, 5, 9, '28 tablets', 'Mymensingh', 'Mymensingh', 'Trishal', 'either', RequestStatus::Active, 3, [6 => 77]],
                [7, 2, 4, '20 tablets', 'Chattogram', 'Chattogram', 'Kotwali', 'donation', RequestStatus::Cancelled, 20, []],
            ];

            return collect($rows)->map(function ($r) {
                $medicine = self::catalogue()->get($r[1]);

                return (object) [
                    'id' => $r[0],
                    'medicine' => $medicine,
                    'name' => $medicine->brand,
                    'generic' => $medicine->generic,
                    'strength' => $medicine->strength,
                    'form' => $medicine->form,
                    'owner' => self::user($r[2]),
                    'quantity' => $r[3],
                    'division' => $r[4],
                    'district' => $r[5],
                    'upazila' => $r[6],
                    'preference' => $r[7],
                    'status' => $r[8],
                    'notes' => 'Needed for a family member. Can collect from a public place nearby.',
                    'created_at' => self::days($r[9], 2),
                    'matches' => collect($r[10])->map(fn ($score, $listingId) => (object) [
                        'listing' => self::listing($listingId),
                        'relevance' => $score,
                    ])->values(),
                ];
            })->keyBy('id');
        });
    }

    public static function request(int $id): ?object
    {
        return self::requests()->get($id);
    }

    public static function preferenceLabel(string $preference): string
    {
        return ['donation' => 'Donation only', 'affordable_access' => 'Affordable Access only', 'either' => 'Donation or Affordable Access'][$preference] ?? $preference;
    }

    /* ---------------------------------------------------- Messages & conversations */

    public static function conversations(): Collection
    {
        return self::memo('conversations', fn () => collect([
            [1, 2, 'listing', 1, 'Yes, it is still available. I can meet near Dhanmondi Lake on Saturday.', 0, 2, InteractionStatus::Requested],
            [2, 5, 'listing', 7, 'Thank you! Please confirm the expiry date on the strip.', 0, 26, InteractionStatus::Requested],
            [3, 3, 'request', 3, 'Your request is waiting for the reviewer. I will hold the strips until then.', 1, 50, InteractionStatus::Reserved],
            [4, 6, 'listing', 11, 'Received safely. Thank you so much for your help.', 0, 900, InteractionStatus::Completed],
        ])->map(fn ($c) => (object) [
            'id' => $c[0],
            'other' => self::user($c[1]),
            'subject_type' => $c[2],
            'subject' => $c[2] === 'listing' ? self::listing($c[3]) : self::request($c[3]),
            'last_message' => $c[4],
            'unread' => $c[0] === 1 ? 2 : $c[5],
            'updated_at' => Carbon::now()->subMinutes($c[6]),
            'status' => $c[7],
        ])->keyBy('id'));
    }

    public static function conversation(int $id): ?object
    {
        return self::conversations()->get($id);
    }

    public static function messages(int $conversationId): Collection
    {
        $script = [
            [false, 'Assalamu alaikum. Is the Napa 500 still available?', 30],
            [true, 'Walaikum assalam. Yes, two sealed strips. Expiry is next year.', 28],
            [false, 'That would really help. Could we meet somewhere public?', 25],
            [true, 'Sure. Dhanmondi Lake, near the main gate, Saturday 11am?', 20],
            [false, 'Perfect. I will mark the request once we meet.', 4],
            [true, 'Yes, it is still available. I can meet near Dhanmondi Lake on Saturday.', 2],
        ];

        return collect($script)->map(fn ($m, $i) => (object) [
            'id' => $i + 1,
            'conversation_id' => $conversationId,
            'mine' => ! $m[0],
            'body' => $m[1],
            'sent_at' => Carbon::now()->subMinutes($m[2] * 10),
            'is_read' => $i < 4,
        ]);
    }

    /* ----------------------------------------------------------- Notifications */

    public static function notifications(): Collection
    {
        return self::memo('notifications', fn () => collect([
            ['message', 'New message from Rahim Uddin', 'About your request for Napa 500.', 2, false, ['messages.show', 1]],
            ['match', 'New match for your request', 'Napa 500 in Dhanmondi, Dhaka looks relevant to your request.', 60, false, ['requests.show', 1]],
            ['verification', 'Verification under review', 'An authorised reviewer is checking your submission for Comet 500.', 180, false, ['verification.index', null]],
            ['listing', 'Listing submitted for review', 'Monas 10 will be visible once a reviewer approves it.', 1440, true, ['listings.index', null]],
            ['reservation', 'Medicine reserved for you', 'Farhana Akter reserved Comet 500 while your verification completes.', 2000, true, ['messages.show', 3]],
            ['completion', 'Hand-over completed', 'Your Fexo 120 donation to Arif Chowdhury was marked complete.', 4000, true, ['history', null]],
            ['report', 'Report update', 'Your report about a listing was reviewed and resolved.', 6000, true, ['notifications.index', null]],
            ['announcement', 'Safety reminder', 'Always check the expiry date and seal at hand-over.', 9000, true, ['safety', null]],
        ])->map(fn ($n, $i) => (object) [
            'id' => $i + 1,
            'type' => $n[0],
            'title' => $n[1],
            'body' => $n[2],
            'created_at' => Carbon::now()->subMinutes($n[3]),
            'read' => $n[4],
            'url' => $n[5][1] ? route($n[5][0], $n[5][1]) : route($n[5][0]),
        ]));
    }

    /* ---------------------------------------------------- Interactions (hand-overs) */

    public static function interactions(): Collection
    {
        return self::memo('interactions', fn () => collect([
            [1, 1, 2, 'received', InteractionStatus::Requested, 1],
            [2, 9, 3, 'received', InteractionStatus::Reserved, 3],
            [3, 11, 6, 'given', InteractionStatus::Completed, 30],
            [4, 2, 3, 'received', InteractionStatus::Completed, 34],
            [5, 4, 9, 'given', InteractionStatus::Cancelled, 50],
            [6, 6, 6, 'received', InteractionStatus::Disputed, 8],
            [7, 5, 5, 'received', InteractionStatus::Completed, 60],
        ])->map(fn ($t) => (object) [
            'id' => $t[0],
            'listing' => self::listing($t[1]),
            'other' => self::user($t[2]),
            'direction' => $t[3],
            'status' => $t[4],
            'updated_at' => self::days($t[5]),
        ]));
    }

    /* ----------------------------------------------------------- Verifications */

    public static function verifications(): Collection
    {
        return self::memo('verifications', fn () => collect([
            [1, 1, 'Identity check', 'National ID (front)', null, VerificationStatus::Verified, 100, 'Name matches account.'],
            [2, 1, 'Prescription check', 'Prescription photo', 3, VerificationStatus::UnderReview, 2, null],
            [3, 6, 'Prescription check', 'Prescription photo', 5, VerificationStatus::Submitted, 1, null],
            [4, 9, 'Identity check', 'National ID (front)', null, VerificationStatus::ReuploadRequired, 4, 'Photo is blurred. Please upload a clearer image.'],
            [5, 4, 'Identity check', 'National ID (front)', null, VerificationStatus::Rejected, 12, 'Document does not match the account name.'],
        ])->map(fn ($v) => (object) [
            'id' => $v[0],
            'user' => self::user($v[1]),
            'kind' => $v[2],
            'document_type' => $v[3],
            'request' => $v[4] ? self::request($v[4]) : null,
            'status' => $v[5],
            'submitted_at' => self::days($v[6]),
            'reviewer_note' => $v[7],
            'history' => collect([
                ['Submitted', self::days($v[6])],
                ...($v[5] !== VerificationStatus::Submitted ? [['Review started', self::days($v[6])->addHours(5)]] : []),
                ...($v[7] ? [[$v[5]->label(), self::days(max(0, $v[6] - 1))]] : []),
            ])->map(fn ($h) => (object) ['label' => $h[0], 'at' => $h[1]]),
        ])->keyBy('id'));
    }

    public static function verification(int $id): ?object
    {
        return self::verifications()->get($id);
    }

    /* ---------------------------------------------------------------- Reports */

    public static function reports(): Collection
    {
        return self::memo('reports', fn () => collect([
            [1, 'Listing', 'Neotack 150 · Chattogram', 13, 'Prohibited medicine', 2, 4, ReportStatus::Resolved, 9],
            [2, 'User', 'Tanvir Hasan', null, 'Asked for payment outside the platform', 6, 4, ReportStatus::Escalated, 6],
            [3, 'Listing', 'Maxpro 20 · Khulna', 6, 'Packaging looks damaged in photos', 9, 6, ReportStatus::UnderReview, 2],
            [4, 'Message', 'Conversation about Comet 500', 9, 'Shared a phone number in chat', 3, 1, ReportStatus::Rejected, 15],
            [5, 'Listing', 'Fexo 120 · Rajshahi', 5, 'Expiry date may be wrong', 1, 5, ReportStatus::Open, 0],
        ])->map(fn ($r) => (object) [
            'id' => $r[0],
            'target_type' => $r[1],
            'target_label' => $r[2],
            'listing' => $r[3] ? self::listing($r[3]) : null,
            'reason' => $r[4],
            'reporter' => self::user($r[5]),
            'reported_user' => self::user($r[6]),
            'status' => $r[7],
            'created_at' => self::days($r[8], 4),
            'details' => 'The reporter added a short description of the problem here. Evidence files would be stored privately and shown only to moderators.',
        ])->keyBy('id'));
    }

    public static function report(int $id): ?object
    {
        return self::reports()->get($id);
    }

    /* ------------------------------------------------------------- Audit logs */

    public static function auditLogs(): Collection
    {
        return self::memo('audit', fn () => collect([
            [8, 'listing.blocked', 'Listing #13 Neotack 150', 'Prohibited medicine (catalogue rule)', 9],
            [7, 'verification.reupload_requested', 'Verification #4', 'Blurred document', 4],
            [8, 'user.suspended', 'User #4 Tanvir Hasan', 'Escalated report #2', 6],
            [7, 'verification.approved', 'Verification #1', 'Identity matches account', 99],
            [8, 'report.rejected', 'Report #4', 'No contact details found in message', 15],
            [8, 'catalogue.updated', 'Medicine #9 Insulin pen', 'Eligibility set to Not Allowed', 20],
            [8, 'listing.approved', 'Listing #9 Comet 500', 'Catalogue match confirmed', 10],
            [8, 'settings.updated', 'Platform settings', 'Listing review required for new members', 25],
        ])->map(fn ($a, $i) => (object) [
            'id' => $i + 1,
            'actor' => self::user($a[0]),
            'action' => $a[1],
            'target' => $a[2],
            'reason' => $a[3],
            'ip' => '103.••.••.'.(20 + $i),
            'created_at' => self::days($a[4], $i),
        ]));
    }

    /* --------------------------------------------------------------- Partners */

    public static function partners(): Collection
    {
        return self::memo('partners', fn () => collect([
            [1, 'Demo Community Clinic', 'Clinic', 'Dhaka', 'Community (sample plan)', PartnerStatus::UnderReview],
            [2, 'Demo NGO Health Programme', 'NGO', 'Sylhet', 'Programme (sample plan)', PartnerStatus::Prospective],
            [3, 'Demo CSR Sponsor Ltd.', 'CSR sponsor', 'Chattogram', 'Sponsorship (sample plan)', PartnerStatus::Prospective],
        ])->map(fn ($p) => (object) [
            'id' => $p[0], 'name' => $p[1], 'type' => $p[2], 'district' => $p[3], 'plan' => $p[4], 'status' => $p[5],
        ]));
    }

    /* ---------------------------------------------------------- Announcements */

    public static function announcements(): Collection
    {
        return self::memo('announcements', fn () => collect([
            [1, 'Safety reminder: check expiry at hand-over', 'All members', true, 6],
            [2, 'New districts added to search', 'All members', true, 20],
            [3, 'Planned maintenance window', 'All members', false, 0],
        ])->map(fn ($a) => (object) [
            'id' => $a[0], 'title' => $a[1], 'audience' => $a[2], 'sent' => $a[3], 'created_at' => self::days($a[4]),
        ]));
    }
}
