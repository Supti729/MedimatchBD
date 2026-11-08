<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Status history of a verification submission. */
class VerificationReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['reviewer_id', 'from_status', 'to_status', 'note'];

    protected function casts(): array
    {
        return ['from_status' => VerificationStatus::class, 'to_status' => VerificationStatus::class];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
