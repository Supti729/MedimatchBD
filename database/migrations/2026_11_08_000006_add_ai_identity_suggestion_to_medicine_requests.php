<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * For free-text requests (no catalogue medicine chosen), the AI may SUGGEST which catalogue
     * medicine was meant (e.g. "paracitamol 500" -> Napa 500 / Paracetamol). Strength and form must
     * still match exactly. The suggestion is stored separately and shown to the member as a suggestion;
     * it never replaces what they typed.
     */
    public function up(): void
    {
        Schema::table('medicine_requests', function (Blueprint $table) {
            $table->foreignId('suggested_medicine_id')->nullable()->after('medicine_id')->constrained('medicines')->nullOnDelete();
            $table->decimal('suggestion_similarity', 5, 4)->nullable()->after('suggested_medicine_id');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suggested_medicine_id');
            $table->dropColumn('suggestion_similarity');
        });
    }
};
