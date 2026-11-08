<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/** Private chat between a listing owner (donor) and one requester. Only these two can read it. */
class Conversation extends Model
{
    protected $fillable = ['medicine_listing_id', 'donor_id', 'requester_id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'donor_read_at' => 'datetime', 'requester_read_at' => 'datetime'];
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function interaction(): HasOne
    {
        return $this->hasOne(Interaction::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('donor_id', $user->id)->orWhere('requester_id', $user->id));
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, [$this->donor_id, $this->requester_id], true);
    }

    public function otherParticipant(User $user): User
    {
        return $user->id === $this->donor_id ? $this->requester : $this->donor;
    }

    public function readAtFor(User $user): ?Carbon
    {
        return $user->id === $this->donor_id ? $this->donor_read_at : $this->requester_read_at;
    }

    public function markReadFor(User $user): void
    {
        $this->forceFill([$user->id === $this->donor_id ? 'donor_read_at' : 'requester_read_at' => now()])->saveQuietly();
    }

    public function unreadCountFor(User $user): int
    {
        $since = $this->readAtFor($user);

        return $this->messages()->where('sender_id', '!=', $user->id)
            ->when($since, fn ($q) => $q->where('created_at', '>', $since))->count();
    }

    // Display helpers relative to the signed-in member
    protected function other(): Attribute
    {
        return Attribute::get(fn () => auth()->user() ? $this->otherParticipant(auth()->user()) : $this->requester);
    }

    protected function subject(): Attribute
    {
        return Attribute::get(fn () => $this->listing);
    }

    protected function status(): Attribute
    {
        return Attribute::get(fn () => $this->interaction?->status);
    }
}
