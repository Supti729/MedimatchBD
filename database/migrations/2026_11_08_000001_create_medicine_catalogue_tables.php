<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verified medicine identities. Every listing must point to one of these.
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name', 100);
            $table->string('generic_name', 100)->index();
            $table->string('strength', 30);
            $table->string('dosage_form', 30);
            $table->string('eligibility', 30)->default('eligible'); // App\Enums\MedicineEligibility
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['brand_name', 'strength', 'dosage_form']);
        });

        Schema::create('medicine_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->string('alias', 100)->index();
            $table->unique(['medicine_id', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_aliases');
        Schema::dropIfExists('medicines');
    }
};
