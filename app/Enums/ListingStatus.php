<?php

namespace App\Enums;

/** Lifecycle of a medicine listing. Users may only move between allowed states (enforced in Part 2). */
enum ListingStatus: string implements HasBadge
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Available = 'available';
    case Requested = 'requested';
    case Reserved = 'reserved';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending Review',
            self::Available => 'Available',
            self::Requested => 'Requested',
            self::Reserved => 'Reserved',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
            self::Blocked => 'Blocked',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-600',
            self::PendingReview => 'bg-amber-100 text-amber-900',
            self::Available => 'bg-green-100 text-green-800',
            self::Requested => 'bg-blue-100 text-blue-800',
            self::Reserved => 'bg-amber-100 text-amber-900',
            self::Paused => 'bg-slate-100 text-slate-600',
            self::Completed => 'bg-slate-200 text-slate-800',
            self::Cancelled => 'bg-slate-100 text-slate-600',
            self::Rejected => 'bg-red-100 text-red-800',
            self::Blocked => 'bg-red-100 text-red-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => '✎',
            self::PendingReview => '◔',
            self::Available => '✓',
            self::Requested => '↗',
            self::Reserved => '●',
            self::Paused => 'Ⅱ',
            self::Completed => '✔',
            self::Cancelled => '✕',
            self::Rejected => '✕',
            self::Blocked => '⊘',
        };
    }
}
