<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A candidate listing for a request, with a relevance score (0–100). Not a statement of medical equivalence. */
class RequestMatch extends Model
{
    protected $fillable = ['medicine_request_id', 'medicine_listing_id', 'score', 'method', 'notified_at'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MedicineRequest::class, 'medicine_request_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MedicineListing::class, 'medicine_listing_id');
    }

    protected function relevance(): Attribute
    {
        return Attribute::get(fn () => $this->score);
    }
}
