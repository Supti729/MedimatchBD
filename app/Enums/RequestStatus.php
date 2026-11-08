<?php

namespace App\Enums;

/** Lifecycle of a medicine request. */
enum RequestStatus: string implements HasBadge
{
    case Active = 'active';
    case Matching = 'matching';
    case MatchFound = 'match_found';
    case VerificationPending = 'verification_pending';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Matching => 'Matching',
            self::MatchFound => 'Match Found',
            self::VerificationPending => 'Verification Pending',
            self::Reserved => 'Reserved',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Matching => 'bg-blue-100 text-blue-800',
            self::MatchFound => 'bg-brand-100 text-brand-800',
            self::VerificationPending => 'bg-amber-100 text-amber-900',
            self::Reserved => 'bg-amber-100 text-amber-900',
            self::Completed => 'bg-slate-200 text-slate-800',
            self::Cancelled => 'bg-slate-100 text-slate-600',
            self::Expired => 'bg-slate-100 text-slate-600',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => '●',
            self::Matching => '⟳',
            self::MatchFound => '✓',
            self::VerificationPending => '◔',
            self::Reserved => '●',
            self::Completed => '✔',
            self::Cancelled => '✕',
            self::Expired => '⌛',
        };
    }
}
