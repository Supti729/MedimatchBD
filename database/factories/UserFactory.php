<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+8801'.fake()->randomElement(['3', '5', '7', '8', '9']).fake()->unique()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'location_id' => Location::factory(),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        // role/status are not mass-assignable, so set them after making.
        return $this->afterMaking(function (User $user) {
            $user->role ??= Role::User;
            $user->status ??= AccountStatus::Active;
        });
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null, 'phone_verified_at' => null])
            ->afterMaking(fn (User $u) => $u->status = AccountStatus::Pending);
    }

    public function admin(): static
    {
        return $this->afterMaking(fn (User $u) => $u->role = Role::Admin);
    }

    public function reviewer(): static
    {
        return $this->afterMaking(fn (User $u) => $u->role = Role::Reviewer);
    }

    public function suspended(): static
    {
        return $this->afterMaking(fn (User $u) => $u->status = AccountStatus::Suspended);
    }
}
