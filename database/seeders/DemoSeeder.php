<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\PartnerStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Location;
use App\Models\Medicine;
use App\Models\Partner;
use App\Models\Setting;
use App\Models\User;
use App\Services\InteractionService;
use App\Services\ListingService;
use App\Services\MessageService;
use App\Services\ReportService;
use App\Services\RequestService;
use App\Services\VerificationService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * LOCAL DEVELOPMENT ONLY — fictional people and activity for demos.
 * Everything is created through the real services, so the demo data follows
 * the same rules (status transitions, matching, notifications, audit log).
 *
 * All demo accounts use the password: Password123
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $at = fn (string $district, string $upazila) => Location::where(compact('district', 'upazila'))->firstOrFail();
        $med = fn (string $brand) => Medicine::where('brand_name', $brand)->firstOrFail();

        $make = function (string $name, ?string $email, ?string $phone, $location, Role $role = Role::User, bool $identity = false, int $daysAgo = 90) {
            $user = User::firstOrNew(['email' => $email, 'phone' => $phone]);
            $user->fill(['name' => $name, 'password' => 'Password123', 'location_id' => $location->id]);
            $user->role = $role;
            $user->status = AccountStatus::Active;
            $user->email_verified_at = $email ? now() : null;
            $user->phone_verified_at = $email ? null : now();
            $user->identity_verified_at = $identity ? now()->subDays(10) : null;
            $user->created_at = now()->subDays($daysAgo);
            $user->save();

            return $user;
        };

        $admin = $make('Kamal Hossain', 'admin@medimatch.test', null, $at('Dhaka', 'Gulshan'), Role::Admin, true, 200);
        $reviewer = $make('Sumaiya Islam', 'reviewer@medimatch.test', null, $at('Gazipur', 'Gazipur Sadar'), Role::Reviewer, true, 180);
        $nadia = $make('Nadia Rahman', 'nadia@medimatch.test', '+8801711000001', $at('Dhaka', 'Mirpur'), Role::User, true, 120);
        $rahim = $make('Rahim Uddin', 'rahim@medimatch.test', '+8801711000002', $at('Dhaka', 'Dhanmondi'), Role::User, true, 150);
        $farhana = $make('Farhana Akter', 'farhana@medimatch.test', null, $at('Sylhet', 'Sylhet Sadar'), Role::User, true, 140);
        $tanvir = $make('Tanvir Hasan', null, '+8801811000004', $at('Chattogram', 'Kotwali'), Role::User, false, 45);
        $mithila = $make('Mithila Das', 'mithila@medimatch.test', null, $at('Rajshahi', 'Boalia'), Role::User, true, 100);
        $arif = $make('Arif Chowdhury', 'arif@medimatch.test', null, $at('Khulna', 'Khulna Sadar'), Role::User, false, 60);

        // AI-assisted matching is on for the demo. If ai-service isn't running, matching falls back to keywords.
        Setting::put(['ai_matching' => true]);

        $listings = app(ListingService::class);
        $requests = app(RequestService::class);
        $interactions = app(InteractionService::class);
        $messages = app(MessageService::class);

        $list = fn (User $owner, string $brand, int $qty, string $unit, int $months, $location, string $packaging = 'Sealed, undamaged') => $listings->create($owner, [
            'medicine_id' => $med($brand)->id, 'location_id' => $location->id, 'quantity' => $qty, 'unit' => $unit,
            'expires_on' => now()->addMonths($months)->startOfMonth()->toDateString(), 'packaging' => $packaging, 'type' => 'donation',
            'description' => 'Left over after a completed course. Stored at room temperature away from sunlight.',
        ], [], false);

        // Listings (eligible medicines go live; "verification required" ones wait for review)
        $napaRahim = $list($rahim, 'Napa 500', 20, 'tablets', 16, $at('Dhaka', 'Dhanmondi'));
        $list($tanvir, 'Ace Syrup', 1, 'bottles', 9, $at('Chattogram', 'Kotwali'), 'Sealed bottle');
        $list($nadia, 'Napa Extend', 30, 'tablets', 14, $at('Dhaka', 'Mirpur'));
        $list($mithila, 'Fexo 120', 10, 'tablets', 20, $at('Rajshahi', 'Boalia'));
        $list($mithila, 'Napa 500', 10, 'tablets', 8, $at('Rajshahi', 'Paba'));
        $list($arif, 'Maxpro 20', 28, 'tablets', 12, $at('Khulna', 'Khulna Sadar'), 'Outer box opened, strips sealed');
        $seclo = $list($farhana, 'Seclo 20', 14, 'capsules', 11, $at('Sylhet', 'Sylhet Sadar'));
        $list($rahim, 'Orsaline-N', 10, 'sachets', 18, $at('Dhaka', 'Dhanmondi'));
        $list($nadia, 'Monas 10', 15, 'tablets', 10, $at('Dhaka', 'Mirpur')); // pending review
        $comet = $list($farhana, 'Comet 500', 30, 'tablets', 13, $at('Sylhet', 'Golapganj')); // pending review

        Auth::login($admin); // moderation and audit entries need an acting admin
        $listings->moderate($comet, 'approve', 'Catalogue match confirmed; packaging photos fine.');

        // Requests (matching + notifications run automatically)
        $napaRequest = $requests->create($nadia, ['medicine_id' => $med('Napa 500')->id, 'medicine_name' => 'Napa 500', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'quantity' => '10 tablets', 'location_id' => $nadia->location_id, 'preference' => 'either', 'notes' => 'Needed for a family member. Can collect from a public place nearby.']);
        $cometRequest = $requests->create($nadia, ['medicine_id' => $med('Comet 500')->id, 'medicine_name' => 'Comet 500', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'quantity' => '30 tablets', 'location_id' => $nadia->location_id, 'preference' => 'donation']);
        $requests->create($arif, ['medicine_id' => null, 'medicine_name' => 'Ace', 'generic_name' => 'Paracetamol', 'strength' => '120 mg/5 ml', 'dosage_form' => 'Syrup', 'quantity' => '1 bottle', 'location_id' => $at('Khulna', 'Sonadanga')->id, 'preference' => 'donation']);

        // A hand-over in progress: Nadia requests Rahim's Napa 500 and they chat.
        Auth::login($nadia);
        $handover = $interactions->request($napaRahim, $nadia, $napaRequest, 'Assalamu alaikum. Is the Napa 500 still available? I can collect this weekend.');
        $messages->send($handover->conversation, $rahim, 'Walaikum assalam. Yes, two sealed strips. Expiry is next year.');
        $messages->send($handover->conversation, $nadia, 'That would really help. Could we meet near Dhanmondi Lake main gate on Saturday at 11am?');
        $messages->send($handover->conversation, $rahim, 'Yes, that works. See you then.');

        // A completed hand-over: Arif received Seclo 20 from Farhana.
        $done = $interactions->request($seclo, $arif, null, 'Hello, could I please request the Seclo 20?');
        Auth::login($farhana);
        $interactions->reserve($done);
        $messages->send($done->conversation, $farhana, 'Reserved for you. Meeting at the Zindabazar point tomorrow at 4pm?');
        $interactions->complete($done, $farhana);

        // Verification: Nadia's prescription for Comet 500 is waiting for a reviewer.
        $verification = app(VerificationService::class);
        $verification->submit($nadia, 'prescription', $this->sampleDocument('SAMPLE PRESCRIPTION', 600, 800), $cometRequest);
        $idCheck = $verification->submit($tanvir, 'identity', $this->sampleDocument('SAMPLE ID CARD', 800, 500));
        Auth::login($reviewer);
        $verification->startReview($idCheck, $reviewer);
        $verification->decide($idCheck, $reviewer, VerificationStatus::ReuploadRequired, 'The photo is blurred. Please upload a clearer image.');

        // An open report.
        Auth::login($mithila);
        app(ReportService::class)->create($mithila, $comet, 'Expired or wrong expiry date', 'The expiry month on the photos looks different from the listing.');

        Auth::logout();

        // Fictional partner organisations (no payments, no data sharing).
        foreach ([['Demo Community Clinic', 'Clinic', 'Dhaka', 'Community (sample plan)', PartnerStatus::UnderReview], ['Demo NGO Health Programme', 'NGO', 'Sylhet', 'Programme (sample plan)', PartnerStatus::Prospective]] as [$name, $type, $district, $plan, $status]) {
            Partner::firstOrCreate(['name' => $name], compact('type', 'district', 'plan') + ['status' => $status]);
        }

        $this->command?->info('Demo accounts (password "Password123"): admin@medimatch.test, reviewer@medimatch.test, nadia@medimatch.test, rahim@medimatch.test, farhana@medimatch.test, mithila@medimatch.test, arif@medimatch.test, phone 01811000004 (Tanvir).');
    }

    /** A clearly fake placeholder image, so no real document is ever needed for demos. */
    private function sampleDocument(string $label, int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 246, 250, 249));
        $ink = imagecolorallocate($image, 15, 107, 102);
        imagerectangle($image, 10, 10, $width - 11, $height - 11, $ink);
        imagestring($image, 5, 30, 30, $label, $ink);
        imagestring($image, 3, 30, 60, 'Fictional demo file - not a real document', $ink);
        foreach (range(0, 8) as $i) {
            imagefilledrectangle($image, 30, 110 + $i * 40, $width - 30 - ($i % 3) * 80, 122 + $i * 40, imagecolorallocate($image, 211, 234, 230));
        }
        $path = tempnam(sys_get_temp_dir(), 'demo').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, strtolower(str_replace(' ', '-', $label)).'.png', 'image/png', null, true);
    }
}
