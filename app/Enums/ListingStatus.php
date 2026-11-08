<?php

namespace App\Enums;

/** Lifecycle of a medicine listing. Changes go through ListingService, which only allows the transitions below. */
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

    /** States in which a listing appears in public search and can be opened by anyone. */
    public static function public(): array
    {
        return [self::Available, self::Requested, self::Reserved];
    }

    /** Statuses a listing can be closed from by its owner (cancel). */
    public function ownerCanCancel(): bool
    {
        return in_array($this, [self::Draft, self::PendingReview, self::Available, self::Requested, self::Paused], true);
    }

    public function ownerCanEdit(): bool
    {
        return in_array($this, [self::Draft, self::PendingReview, self::Available, self::Paused, self::Rejected], true);
    }

    /**
     * Allowed transitions. Anything not listed here is refused by ListingService,
     * so a user can never jump a listing straight to e.g. "completed".
     */
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Draft => [self::PendingReview, self::Available, self::Cancelled],
            self::PendingReview => [self::Available, self::Rejected, self::Blocked, self::Cancelled, self::Draft],
            self::Available => [self::Requested, self::Reserved, self::Paused, self::Cancelled, self::Blocked, self::PendingReview],
            self::Requested => [self::Available, self::Reserved, self::Cancelled, self::Blocked],
            self::Reserved => [self::Available, self::Requested, self::Completed, self::Blocked],
            self::Paused => [self::Available, self::Cancelled, self::Blocked, self::PendingReview],
            self::Rejected => [self::PendingReview, self::Cancelled],
            self::Completed, self::Cancelled, self::Blocked => [],
        }, true);
    }
}
