<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        config(['medimatch.show_dev_codes' => true]);
    }

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Begum',
            'email' => 'asha@example.com',
            'phone' => '01712345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Mirpur',
            'terms' => '1',
        ], $overrides);
    }

    /* ---------------------------------------------------------------- Registration */

    public function test_member_can_register_and_must_verify(): void
    {
        $response = $this->post('/register', $this->registration());

        $response->assertRedirect(route('verification.notice'))->assertSessionHas('dev_code');
        $user = User::where('email', 'asha@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('+8801712345678', $user->phone, 'phone is stored normalised');
        $this->assertSame(Role::User, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password, 'password is never stored in plain text');

        // Unverified members cannot use member features yet.
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_member_can_register_with_phone_only(): void
    {
        $this->post('/register', $this->registration(['email' => '', 'phone' => '+880 1812-345678']))->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['phone' => '+8801812345678', 'email' => null]);
    }

    public function test_registration_ignores_role_and_status_from_the_form(): void
    {
        $this->post('/register', $this->registration(['role' => 'admin', 'status' => 'active']));
        $user = User::where('email', 'asha@example.com')->firstOrFail();
        $this->assertSame(Role::User, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->status);
    }

    public function test_duplicate_email_and_phone_are_rejected(): void
    {
        $this->member(attributes: ['email' => 'asha@example.com', 'phone' => '+8801712345678']);

        $this->post('/register', $this->registration())->assertSessionHasErrors(['email', 'phone']);
        $this->post('/register', $this->registration(['email' => 'ASHA@example.com', 'phone' => '']))->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_invalid_registration_input_is_rejected(): void
    {
        $this->post('/register', $this->registration(['email' => '', 'phone' => '']))->assertSessionHasErrors(['email', 'phone']);
        $this->post('/register', $this->registration(['phone' => '01212345678']))->assertSessionHasErrors('phone');
        $this->post('/register', $this->registration(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/register', $this->registration(['password_confirmation' => 'different1']))->assertSessionHasErrors('password');
        $this->post('/register', $this->registration(['upazila' => 'Not a real place']))->assertSessionHasErrors('upazila');
        $this->post('/register', $this->registration(['terms' => null]))->assertSessionHasErrors('terms');
        $this->assertGuest();
    }

    /* ---------------------------------------------------------------- Verification */

    public function test_correct_code_verifies_and_activates_the_account(): void
    {
        $this->post('/register', $this->registration());
        $code = session('dev_code');

        $this->post('/verify-account', ['code' => $code])->assertRedirect(route('dashboard'));
        $user = User::firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(AccountStatus::Active, $user->status);
        $this->get('/dashboard')->assertOk();
    }

    public function test_wrong_or_expired_code_is_rejected_and_attempts_are_limited(): void
    {
        $this->post('/register', $this->registration());
        $user = User::firstOrFail();

        $this->post('/verify-account', ['code' => '000000'])->assertSessionHasErrors('code');
        foreach (range(1, 5) as $i) {
            $this->post('/verify-account', ['code' => '000000']);
        }
        $this->post('/verify-account', ['code' => session('dev_code') ?? '123456'])->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);

        $user->forceFill(['verification_code' => Hash::make('111111'), 'verification_code_expires_at' => now()->subMinute(), 'verification_attempts' => 0])->save();
        $this->post('/verify-account', ['code' => '111111'])->assertSessionHasErrors('code');
    }

    /* ---------------------------------------------------------------- Login / logout */

    public function test_member_can_log_in_with_email_or_phone(): void
    {
        $user = $this->member(attributes: ['email' => 'nadia@example.com', 'phone' => '+8801711000001', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'nadia@example.com', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/login', ['login' => '01711-000001', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_and_login_is_throttled(): void
    {
        $this->member(attributes: ['email' => 'nadia@example.com', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'nadia@example.com', 'password' => 'wrong-password'])->assertSessionHasErrors('login');
        $this->assertGuest();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['login' => 'nadia@example.com', 'password' => 'wrong-password']);
        }
        $response = $this->post('/login', ['login' => 'nadia@example.com', 'password' => 'secret123']);
        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_suspended_member_cannot_log_in_and_is_signed_out(): void
    {
        $user = $this->member(attributes: ['email' => 'nadia@example.com', 'password' => 'secret123']);
        $user->forceFill(['status' => AccountStatus::Suspended])->save();
        $this->post('/login', ['login' => 'nadia@example.com', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest();

        // Already signed in when suspended: signed out on the next request.
        $user->forceFill(['status' => AccountStatus::Active])->save();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->forceFill(['status' => AccountStatus::Suspended])->save();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_login_only_accepts_staff(): void
    {
        $this->member(attributes: ['email' => 'member@example.com', 'password' => 'secret123']);
        $admin = $this->member(attributes: ['email' => 'admin@example.com', 'password' => 'secret123']);
        $admin->forceFill(['role' => Role::Admin])->save();

        $this->post('/admin/login', ['login' => 'member@example.com', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post('/admin/login', ['login' => 'admin@example.com', 'password' => 'secret123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login', 'actor_id' => $admin->id]);
    }

    public function test_guests_are_sent_to_the_right_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/my/listings/create')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->post('/my/listings', [])->assertRedirect(route('login'));
    }

    /* ---------------------------------------------------------------- Password reset */

    public function test_password_can_be_reset_by_email(): void
    {
        Notification::fake();
        $user = $this->member(attributes: ['email' => 'nadia@example.com', 'password' => 'old-pass1']);

        $this->post('/forgot-password', ['email' => 'nadia@example.com'])->assertSessionHas('success');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('success'); // same answer, no account discovery

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post('/reset-password', ['token' => $notification->token, 'email' => $user->email, 'password' => 'new-pass1', 'password_confirmation' => 'new-pass1'])
                ->assertRedirect(route('login'));

            return true;
        });
        $this->assertTrue(Hash::check('new-pass1', $user->fresh()->password));
    }

    public function test_reset_with_invalid_token_fails(): void
    {
        $user = $this->member(attributes: ['email' => 'nadia@example.com', 'password' => 'old-pass1']);
        $this->post('/reset-password', ['token' => 'bad', 'email' => $user->email, 'password' => 'new-pass1', 'password_confirmation' => 'new-pass1'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-pass1', $user->fresh()->password));
    }
}
