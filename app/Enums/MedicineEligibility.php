<?php

namespace App\Enums;

/** Catalogue rule deciding whether a medicine may be listed, and whether a reviewer check is needed. */
enum MedicineEligibility: string implements HasBadge
{
    case Eligible = 'eligible';
    case VerificationRequired = 'verification_required';
    case NotAllowed = 'not_allowed';

    public function label(): string
    {
        return match ($this) {
            self::Eligible => 'Eligible',
            self::VerificationRequired => 'Verification Required',
            self::NotAllowed => 'Not Allowed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Eligible => 'bg-green-100 text-green-800',
            self::VerificationRequired => 'bg-amber-100 text-amber-900',
            self::NotAllowed => 'bg-red-100 text-red-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Eligible => '✓',
            self::VerificationRequired => '◔',
            self::NotAllowed => '⊘',
        };
    }
}
