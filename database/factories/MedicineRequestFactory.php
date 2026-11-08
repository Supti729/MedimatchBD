<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Location;
use App\Models\MedicineRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedicineRequest> */
class MedicineRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'medicine_name' => 'Napa 500',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
            'quantity' => '10 tablets',
            'location_id' => Location::factory(),
            'preference' => 'either',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MedicineRequest $r) {
            $r->status ??= RequestStatus::Active;
            $r->expires_at ??= now()->addDays(30);
        });
    }
}
