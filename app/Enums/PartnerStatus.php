<?php

namespace App\Enums;

/** Status of a future partner organisation account. */
enum PartnerStatus: string implements HasBadge
{
    case Prospective = 'prospective';
    case UnderReview = 'under_review';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Prospective => 'Prospective',
            self::UnderReview => 'Under Review',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Prospective => 'bg-slate-100 text-slate-600',
            self::UnderReview => 'bg-amber-100 text-amber-900',
            self::Active => 'bg-green-100 text-green-800',
            self::Suspended => 'bg-red-100 text-red-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Prospective => '○',
            self::UnderReview => '◔',
            self::Active => '●',
            self::Suspended => '⊘',
        };
    }
}
