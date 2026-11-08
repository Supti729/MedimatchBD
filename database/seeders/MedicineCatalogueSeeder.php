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
            ['Napa 500', 'Paracetamol', '500 mg', 'Tablet', E::Eligible, ['Napa', 'Paracetamol 500', 'Acetaminophen', 'নাপা', 'প্যারাসিটামল']],
            ['Napa Extend', 'Paracetamol', '665 mg', 'Tablet', E::Eligible, ['Napa ER', 'Paracetamol extended release', 'নাপা এক্সটেন্ড', 'প্যারাসিটামল']],
            ['Ace Syrup', 'Paracetamol', '120 mg/5 ml', 'Syrup', E::Eligible, ['Ace', 'Paracetamol suspension', 'এইস সিরাপ', 'প্যারাসিটামল সিরাপ']],
            ['Seclo 20', 'Omeprazole', '20 mg', 'Capsule', E::Eligible, ['Seclo', 'Omeprazole 20', 'সেকলো', 'ওমিপ্রাজল']],
            ['Maxpro 20', 'Esomeprazole', '20 mg', 'Tablet', E::Eligible, ['Maxpro', 'Esomeprazole 20', 'ম্যাক্সপ্রো', 'ইসোমিপ্রাজল']],
            ['Fexo 120', 'Fexofenadine', '120 mg', 'Tablet', E::Eligible, ['Fexo', 'Fexofenadine HCl', 'ফেক্সো', 'ফেক্সোফেনাডিন']],
            ['Alatrol 10', 'Cetirizine', '10 mg', 'Tablet', E::Eligible, ['Alatrol', 'Cetirizine HCl', 'এলাট্রল', 'সেটিরিজিন']],
            ['Orsaline-N', 'Oral rehydration salts', '10.25 g', 'Powder', E::Eligible, ['ORS', 'Orsaline', 'Saline', 'খাবার স্যালাইন', 'ওরস্যালাইন']],
            ['Monas 10', 'Montelukast', '10 mg', 'Tablet', E::VerificationRequired, ['Monas', 'Montelukast sodium', 'মোনাস', 'মন্টিলুকাস্ট']],
            ['Comet 500', 'Metformin', '500 mg', 'Tablet', E::VerificationRequired, ['Comet', 'Metformin HCl', 'কমেট', 'মেটফরমিন']],
            ['Osartil 50', 'Losartan', '50 mg', 'Tablet', E::VerificationRequired, ['Osartil', 'Losartan potassium', 'অসারটিল', 'লোসারটান']],
            ['Azithrocin 500', 'Azithromycin', '500 mg', 'Tablet', E::VerificationRequired, ['Azithrocin', 'Azithromycin 500', 'অ্যাজিথ্রোসিন', 'অ্যাজিথ্রোমাইসিন']],
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
