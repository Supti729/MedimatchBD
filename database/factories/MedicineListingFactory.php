<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Location;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedicineListing> */
class MedicineListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'medicine_id' => Medicine::factory(),
            'location_id' => Location::factory(),
            'quantity' => 20,
            'unit' => 'tablets',
            'expires_on' => now()->addYear()->startOfMonth(),
            'packaging' => 'Sealed, undamaged',
            'description' => null,
            'type' => ListingType::Donation,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MedicineListing $l) {
            $l->status ??= ListingStatus::Available;
            $l->published_at ??= now();
        });
    }

    public function status(ListingStatus $status): static
    {
        return $this->afterMaking(fn (MedicineListing $l) => $l->status = $status);
    }
}
