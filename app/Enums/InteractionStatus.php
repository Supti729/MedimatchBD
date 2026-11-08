<?php

namespace App\Enums;

/** A donation / affordable-access hand-over between two members. */
enum InteractionStatus: string implements HasBadge
{
    case Requested = 'requested';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Reserved => 'Reserved',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Disputed => 'Disputed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'bg-blue-100 text-blue-800',
            self::Reserved => 'bg-amber-100 text-amber-900',
            self::Completed => 'bg-slate-200 text-slate-800',
            self::Cancelled => 'bg-slate-100 text-slate-600',
            self::Disputed => 'bg-orange-100 text-orange-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Requested => '↗',
            self::Reserved => '●',
            self::Completed => '✔',
            self::Cancelled => '✕',
            self::Disputed => '⚑',
        };
    }
}
