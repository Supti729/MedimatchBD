<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data needed in every environment.
        $this->call([LocationSeeder::class, MedicineCatalogueSeeder::class]);

        // Fictional demo accounts and activity: local development only, never production.
        if (app()->environment('local') && class_exists(DemoSeeder::class)) {
            $this->call(DemoSeeder::class);
        }
    }
}
