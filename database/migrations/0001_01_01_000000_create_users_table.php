<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bangladesh administrative areas. One row per upazila / thana.
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('division', 40);
            $table->string('district', 40);
            $table->string('upazila', 60);
            $table->unique(['district', 'upazila']);
            $table->index(['division', 'district']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            // A member signs up with an email, a Bangladeshi phone number, or both (at least one is required).
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 16)->nullable()->unique(); // stored normalised: +8801XXXXXXXXX
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 20)->default('user')->index();     // App\Enums\Role
            $table->string('status', 20)->default('pending')->index(); // App\Enums\AccountStatus
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('avatar_path')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->json('privacy')->nullable();
            // Development-mode one-time code (hashed). No SMS/email provider is configured.
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->unsignedTinyInteger('verification_attempts')->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('locations');
    }
};
