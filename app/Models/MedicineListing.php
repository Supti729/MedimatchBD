<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MedicineListing extends Model
{
    use HasFactory;

    /** status, review fields and timestamps are set only through ListingService. */
    protected $fillable = ['medicine_id', 'location_id', 'quantity', 'unit', 'expires_on', 'batch_number', 'packaging', 'description', 'type'];

    protected $hidden = ['batch_number'];

    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'type' => ListingType::class,
            'status' => ListingStatus::class,
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------ Relations */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('position');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_listings');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /* --------------------------------------------------------------- Scopes */

    /** Only listings anyone may see. Blocked, draft, paused or unreviewed listings never leak. */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ListingStatus::public())
            ->whereDate('expires_on', '>=', now()->toDateString())
            ->whereHas('owner', fn ($q) => $q->where('status', 'active'));
    }

    public function scopeWithCardData(Builder $query): Builder
    {
        return $query->with(['medicine.aliases', 'location', 'owner']);
    }

    public function isPublic(): bool
    {
        return in_array($this->status, ListingStatus::public(), true)
            && $this->expires_on->endOfDay()->isFuture()
            && $this->owner?->status?->value === 'active';
    }

    /* ---------------------------------------- Display helpers used by views */

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->medicine?->brand_name);
    }

    protected function generic(): Attribute
    {
        return Attribute::get(fn () => $this->medicine?->generic_name);
    }

    protected function strength(): Attribute
    {
        return Attribute::get(fn () => $this->medicine?->strength);
    }

    protected function form(): Attribute
    {
        return Attribute::get(fn () => $this->medicine?->dosage_form);
    }

    protected function eligibility(): Attribute
    {
        return Attribute::get(fn () => $this->medicine?->eligibility);
    }

    protected function division(): Attribute
    {
        return Attribute::get(fn () => $this->location?->division);
    }

    protected function district(): Attribute
    {
        return Attribute::get(fn () => $this->location?->district);
    }

    protected function upazila(): Attribute
    {
        return Attribute::get(fn () => $this->location?->upazila);
    }

    protected function expiresAt(): Attribute
    {
        return Attribute::get(fn () => $this->expires_on);
    }

    protected function quantityLabel(): Attribute
    {
        return Attribute::get(fn () => $this->quantity.' '.($this->quantity === 1 ? rtrim($this->unit, 's') : $this->unit));
    }

    /** Every listing is tied to a verified catalogue entry. */
    protected function catalogueVerified(): Attribute
    {
        return Attribute::get(fn () => $this->medicine_id !== null);
    }
}
