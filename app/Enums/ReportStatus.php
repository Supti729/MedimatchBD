<?php

namespace App\Enums;

/** State of a user report about a listing, user or message. */
enum ReportStatus: string implements HasBadge
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderReview => 'Under Review',
            self::Resolved => 'Resolved',
            self::Rejected => 'Rejected',
            self::Escalated => 'Escalated',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'bg-orange-100 text-orange-800',
            self::UnderReview => 'bg-amber-100 text-amber-900',
            self::Resolved => 'bg-green-100 text-green-800',
            self::Rejected => 'bg-slate-100 text-slate-600',
            self::Escalated => 'bg-red-100 text-red-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Open => '⚑',
            self::UnderReview => '◔',
            self::Resolved => '✓',
            self::Rejected => '✕',
            self::Escalated => '▲',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::UnderReview, self::Escalated], true);
    }
}
