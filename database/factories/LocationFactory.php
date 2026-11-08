<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return ['division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Area '.fake()->unique()->numberBetween(1, 1_000_000)];
    }
}
