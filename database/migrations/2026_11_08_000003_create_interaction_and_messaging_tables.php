<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A private chat between a listing's owner (donor) and one requester.
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('donor_read_at')->nullable();
            $table->timestamp('requester_read_at')->nullable();
            $table->timestamps();
            $table->unique(['medicine_listing_id', 'requester_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        // The hand-over itself: requested -> reserved -> completed (or cancelled / disputed).
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medicine_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('requested')->index(); // App\Enums\InteractionStatus
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
