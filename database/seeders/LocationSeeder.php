<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];
        foreach (require database_path('data/locations.php') as $division => $districts) {
            foreach ($districts as $district => $upazilas) {
                foreach ($upazilas as $upazila) {
                    $rows[] = compact('division', 'district', 'upazila');
                }
            }
        }
        Location::upsert($rows, ['district', 'upazila'], ['division']);
        cache()->forget('locations.tree');
    }
}
