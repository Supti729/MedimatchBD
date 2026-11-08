<?php

namespace Tests;

use App\Models\Location;
use App\Models\Medicine;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Database\Seeders\MedicineCatalogueSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests don't need compiled CSS/JS, so they run without `npm run build` (e.g. in CI).
        $this->withoutVite();
    }

    /** Reference data most tests need: locations and the medicine catalogue. */
    protected function seedReferenceData(): void
    {
        $this->seed([LocationSeeder::class, MedicineCatalogueSeeder::class]);
    }

    protected function location(string $district = 'Dhaka', string $upazila = 'Mirpur'): Location
    {
        return Location::where(compact('district', 'upazila'))->firstOrFail();
    }

    protected function medicine(string $brand = 'Napa 500'): Medicine
    {
        return Medicine::where('brand_name', $brand)->firstOrFail();
    }

    /** A verified, active member living in the given area. */
    protected function member(string $district = 'Dhaka', string $upazila = 'Mirpur', array $attributes = []): User
    {
        return User::factory()->create(['location_id' => $this->location($district, $upazila)->id, ...$attributes]);
    }
}
