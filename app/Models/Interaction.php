<?php

namespace App\Models;

use App\Enums\InteractionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A hand-over between a donor and a requester for one listing. Status changes go through InteractionService. */
class Interaction extends Model
{
    protected $fillable = ['medicine_listing_id', 'donor_id', 'requester_id', 'medicine_request_id', 'conversation_id'];

    protected function casts(): array
    {
        return [
            'status' => InteractionStatus::class,
            'reserved_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MedicineListing::class, 'medicine_listing_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function medicineRequest(): BelongsTo
    {
        return $this->belongsTo(MedicineRequest::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopeInvolving(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('donor_id', $user->id)->orWhere('requester_id', $user->id));
    }
}
