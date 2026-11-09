<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_active_administrator(): void
    {
        $this->artisan('medimatch:create-admin', ['--name' => 'Supti Saha', '--email' => 'Supti@Example.com', '--password' => 'StrongPass123'])
            ->assertSuccessful();

        $admin = User::where('email', 'supti@example.com')->firstOrFail();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertSame(AccountStatus::Active, $admin->status);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('StrongPass123', $admin->password));
    }

    public function test_it_rejects_weak_input(): void
    {
        $this->artisan('medimatch:create-admin', ['--name' => 'X', '--email' => 'not-an-email', '--password' => 'short'])
            ->assertFailed();
        $this->assertSame(0, User::count());
    }
}
