<?php

namespace Database\Factories;

use App\Enums\MedicineEligibility;
use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Medicine> */
class MedicineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_name' => 'Brand '.fake()->unique()->numberBetween(1, 1_000_000),
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'dosage_form' => 'Tablet',
            'eligibility' => MedicineEligibility::Eligible,
            'is_active' => true,
        ];
    }

    public function needsVerification(): static
    {
        return $this->state(['eligibility' => MedicineEligibility::VerificationRequired]);
    }

    public function notAllowed(): static
    {
        return $this->state(['eligibility' => MedicineEligibility::NotAllowed]);
    }
}
