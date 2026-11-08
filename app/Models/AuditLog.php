<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/** Append-only record of administrative actions. Rows can be created but never changed or deleted. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'description', 'reason', 'ip_address', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be modified.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Display helpers
    protected function target(): Attribute
    {
        return Attribute::get(fn () => $this->description);
    }

    /** Partially masked for display. */
    protected function ip(): Attribute
    {
        return Attribute::get(fn () => $this->ip_address ? preg_replace('/^(\d+)\.\d+\.\d+\.(\d+)$/', '$1.••.••.$2', $this->ip_address) : '—');
    }
}
