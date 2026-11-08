<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineRequest extends Model
{
    use HasFactory;

    protected $fillable = ['medicine_id', 'medicine_name', 'generic_name', 'strength', 'dosage_form', 'quantity', 'location_id', 'preference', 'notes'];

    public const PREFERENCES = [
        'donation' => 'Donation only',
        'either' => 'Donation or Affordable Access',
        'affordable_access' => 'Affordable Access only',
    ];

    protected function casts(): array
    {
        return ['status' => RequestStatus::class, 'expires_at' => 'datetime', 'completed_at' => 'datetime', 'suggestion_similarity' => 'float'];
    }

    /** AI-suggested catalogue medicine for a free-text request (a suggestion only — see MatchingService). */
    public function suggestedMedicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'suggested_medicine_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(RequestMatch::class)->orderByDesc('score');
    }

    public function verificationSubmissions(): HasMany
    {
        return $this->hasMany(VerificationSubmission::class);
    }

    public function preferenceLabel(): string
    {
        return self::PREFERENCES[$this->preference] ?? $this->preference;
    }

    // Display helpers (Part 1 naming)
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->medicine_name);
    }

    protected function generic(): Attribute
    {
        return Attribute::get(fn () => $this->generic_name ?? $this->medicine?->generic_name);
    }

    protected function form(): Attribute
    {
        return Attribute::get(fn () => $this->dosage_form);
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
}
