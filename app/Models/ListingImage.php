<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Packaging photos are meant to be public, so they live on the public disk under random names. */
class ListingImage extends Model
{
    protected $fillable = ['path', 'position'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MedicineListing::class, 'medicine_listing_id');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->path));
    }
}
