<?php

namespace App\Enums;

/** State of a verification submission (identity or prescription check by an authorised reviewer). */
enum VerificationStatus: string implements HasBadge
{
    case NotSubmitted = 'not_submitted';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case ReuploadRequired = 'reupload_required';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not Submitted',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::ReuploadRequired => 'Re-upload Required',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotSubmitted => 'bg-slate-100 text-slate-600',
            self::Submitted => 'bg-blue-100 text-blue-800',
            self::UnderReview => 'bg-amber-100 text-amber-900',
            self::Verified => 'bg-brand-100 text-brand-800',
            self::Rejected => 'bg-red-100 text-red-800',
            self::ReuploadRequired => 'bg-orange-100 text-orange-800',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NotSubmitted => '○',
            self::Submitted => '↑',
            self::UnderReview => '◔',
            self::Verified => '✓',
            self::Rejected => '✕',
            self::ReuploadRequired => '↻',
        };
    }
}
