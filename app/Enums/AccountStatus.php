<?php

namespace App\Enums;

/** Account standing, controlled by administrators only. */
enum AccountStatus: string implements HasBadge
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending Verification',
            self::Suspended => 'Suspended',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Pending => 'bg-amber-100 text-amber-900',
            self::Suspended => 'bg-red-100 text-red-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => '●',
            self::Pending => '◔',
            self::Suspended => '⊘',
        };
    }
}
