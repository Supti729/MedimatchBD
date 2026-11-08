<?php

namespace App\Enums;

/**
 * Account roles. One regular account can both offer and request medicine.
 * Roles are only ever changed by an administrator, never by the user.
 */
enum Role: string
{
    case User = 'user';
    case Reviewer = 'reviewer'; // authorised to review verification documents
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Member',
            self::Reviewer => 'Authorised reviewer',
            self::Admin => 'Administrator',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::User;
    }
}
