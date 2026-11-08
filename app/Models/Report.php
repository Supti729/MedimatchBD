<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    public const CATEGORIES = [
        'Expired or wrong expiry date',
        'Damaged or opened packaging',
        'Restricted or prohibited medicine',
        'Offered for sale / asking for money',
        'Sharing contact details',
        'Harassment or abuse',
        'Suspicious or misleading',
        'Other',
    ];

    protected $fillable = ['category', 'details'];

    protected function casts(): array
    {
        return ['status' => ReportStatus::class, 'resolved_at' => 'datetime'];
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    protected function targetType(): Attribute
    {
        return Attribute::get(fn () => match ($this->reportable_type) {
            'listing' => 'Listing',
            'conversation' => 'Conversation',
            default => 'User',
        });
    }

    protected function targetLabel(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            $this->reportable instanceof MedicineListing => $this->reportable->name.' · '.$this->reportable->district,
            $this->reportable instanceof Conversation => 'Conversation about '.$this->reportable->listing?->name,
            $this->reportable instanceof User => $this->reportable->name,
            default => 'Removed item',
        });
    }

    protected function listing(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            $this->reportable instanceof MedicineListing => $this->reportable,
            $this->reportable instanceof Conversation => $this->reportable->listing,
            default => null,
        });
    }

    protected function reason(): Attribute
    {
        return Attribute::get(fn () => $this->category);
    }
}
