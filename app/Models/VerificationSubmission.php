<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A private document submitted for an identity or prescription check. The file is never publicly reachable. */
class VerificationSubmission extends Model
{
    public const KINDS = ['identity' => 'Identity check', 'prescription' => 'Prescription check'];

    protected $fillable = ['kind', 'medicine_request_id', 'document_path', 'original_name', 'mime_type', 'size_bytes'];

    protected $hidden = ['document_path'];

    protected function casts(): array
    {
        return ['status' => VerificationStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MedicineRequest::class, 'medicine_request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VerificationReview::class)->orderBy('created_at')->orderBy('id');
    }

    protected function kindLabel(): Attribute
    {
        return Attribute::get(fn () => self::KINDS[$this->kind] ?? $this->kind);
    }

    protected function documentType(): Attribute
    {
        return Attribute::get(fn () => $this->mime_type === 'application/pdf' ? 'PDF document' : 'Image');
    }

    protected function submittedAt(): Attribute
    {
        return Attribute::get(fn () => $this->created_at);
    }
}
