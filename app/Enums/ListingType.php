<?php

namespace App\Enums;

/** How a listing is offered. Affordable Access is only shown where an approved programme allows it. */
enum ListingType: string implements HasBadge
{
    case Donation = 'donation';
    case AffordableAccess = 'affordable_access';

    public function label(): string
    {
        return match ($this) {
            self::Donation => 'Donation',
            self::AffordableAccess => 'Affordable Access',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Donation => 'bg-brand-100 text-brand-800',
            self::AffordableAccess => 'bg-warm-100 text-warm-700',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Donation => '♥',
            self::AffordableAccess => '৳',
        };
    }
}
