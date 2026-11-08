<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->string('unit', 20);
            $table->date('expires_on');
            $table->string('batch_number', 40)->nullable(); // reviewers only
            $table->string('packaging', 60);
            $table->text('description')->nullable();
            $table->string('type', 30)->default('donation');  // App\Enums\ListingType
            $table->string('status', 30)->default('draft');   // App\Enums\ListingStatus
            $table->string('review_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'location_id']);
        });

        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('saved_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'medicine_listing_id']);
        });

        Schema::create('medicine_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medicine_name', 100);
            $table->string('generic_name', 100)->nullable();
            $table->string('strength', 30);
            $table->string('dosage_form', 30);
            $table->string('quantity', 40);
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('preference', 30)->default('either');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active')->index(); // App\Enums\RequestStatus
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Candidate listings found for a request. `method` is "keyword" now and "semantic" after Part 3.
        Schema::create('request_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->string('method', 20)->default('keyword');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['medicine_request_id', 'medicine_listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_matches');
        Schema::dropIfExists('medicine_requests');
        Schema::dropIfExists('saved_listings');
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('medicine_listings');
    }
};
