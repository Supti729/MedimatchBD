<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const AUDIENCES = ['all' => 'All members', 'division' => 'Members in one division', 'donors' => 'Members with active listings'];

    protected $fillable = ['title', 'body', 'audience', 'audience_division'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function sent(): Attribute
    {
        return Attribute::get(fn () => $this->sent_at !== null);
    }

    protected function audienceLabel(): Attribute
    {
        return Attribute::get(fn () => (self::AUDIENCES[$this->audience] ?? $this->audience).($this->audience_division ? " ({$this->audience_division})" : ''));
    }
}
