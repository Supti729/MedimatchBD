<?php

namespace Database\Seeders;

use App\Enums\MedicineEligibility as E;
use App\Models\Medicine;
use Illuminate\Database\Seeder;

/**
 * Starter catalogue for the prototype. Eligibility values are illustrative project
 * rules, not regulatory advice; a real deployment must set them with qualified
 * pharmacists against current Bangladesh regulations.
 */
class MedicineCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $medicines = [
            ['Napa 500', 'Paracetamol', '500 mg', 'Tablet', E::Eligible, ['Napa', 'Paracetamol 500', 'Acetaminophen', 'নাপা']],
            ['Napa Extend', 'Paracetamol', '665 mg', 'Tablet', E::Eligible, ['Napa ER', 'Paracetamol extended release']],
            ['Ace Syrup', 'Paracetamol', '120 mg/5 ml', 'Syrup', E::Eligible, ['Ace', 'Paracetamol suspension']],
            ['Seclo 20', 'Omeprazole', '20 mg', 'Capsule', E::Eligible, ['Seclo', 'Omeprazole 20']],
            ['Maxpro 20', 'Esomeprazole', '20 mg', 'Tablet', E::Eligible, ['Maxpro', 'Esomeprazole 20']],
            ['Fexo 120', 'Fexofenadine', '120 mg', 'Tablet', E::Eligible, ['Fexo', 'Fexofenadine HCl']],
            ['Alatrol 10', 'Cetirizine', '10 mg', 'Tablet', E::Eligible, ['Alatrol', 'Cetirizine HCl']],
            ['Orsaline-N', 'Oral rehydration salts', '10.25 g', 'Powder', E::Eligible, ['ORS', 'Orsaline', 'Saline']],
            ['Monas 10', 'Montelukast', '10 mg', 'Tablet', E::VerificationRequired, ['Monas', 'Montelukast sodium']],
            ['Comet 500', 'Metformin', '500 mg', 'Tablet', E::VerificationRequired, ['Comet', 'Metformin HCl']],
            ['Osartil 50', 'Losartan', '50 mg', 'Tablet', E::VerificationRequired, ['Osartil', 'Losartan potassium']],
            ['Azithrocin 500', 'Azithromycin', '500 mg', 'Tablet', E::VerificationRequired, ['Azithrocin', 'Azithromycin 500']],
            ['Insulin pen', 'Insulin', '100 IU/ml', 'Injection', E::NotAllowed, ['Insulin']],
            ['Neotack 150', 'Ranitidine', '150 mg', 'Tablet', E::NotAllowed, ['Ranitidine']],
        ];

        foreach ($medicines as [$brand, $generic, $strength, $form, $eligibility, $aliases]) {
            $medicine = Medicine::updateOrCreate(
                ['brand_name' => $brand, 'strength' => $strength, 'dosage_form' => $form],
                ['generic_name' => $generic, 'eligibility' => $eligibility, 'is_active' => true],
            );
            foreach ($aliases as $alias) {
                $medicine->aliases()->firstOrCreate(['alias' => $alias]);
            }
        }
    }
}
