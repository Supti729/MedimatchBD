<?php

namespace App\Services\Ai;

use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;

/**
 * Builds the ONLY texts that are ever sent to the AI service: medicine descriptions.
 * No member names, contact details, locations, notes or documents are included.
 */
class MedicineText
{
    public static function medicine(Medicine $medicine): string
    {
        $aliases = $medicine->relationLoaded('aliases') ? $medicine->aliases->pluck('alias')->implode(', ') : '';

        return trim("{$medicine->brand_name} — {$medicine->generic_name} {$medicine->strength} {$medicine->dosage_form}".($aliases ? ". Also known as: {$aliases}" : ''));
    }

    public static function listing(MedicineListing $listing): string
    {
        return self::medicine($listing->medicine);
    }

    public static function request(MedicineRequest $request): string
    {
        return trim(implode(' ', array_filter([$request->medicine_name, $request->generic_name, $request->strength, $request->dosage_form])));
    }
}
