<?php

namespace App\Models;

use App\Enums\MedicineEligibility;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A verified catalogue entry: brand + strength + dosage form, with its eligibility rule. */
class Medicine extends Model
{
    use HasFactory;

    protected $fillable = ['brand_name', 'generic_name', 'strength', 'dosage_form', 'eligibility', 'is_active'];

    protected function casts(): array
    {
        return ['eligibility' => MedicineEligibility::class, 'is_active' => 'boolean'];
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(MedicineAlias::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MedicineListing::class);
    }

    public function label(): string
    {
        return "{$this->brand_name} — {$this->generic_name} {$this->strength} {$this->dosage_form}";
    }

    // Short names used by the views (Part 1 naming).
    protected function brand(): Attribute
    {
        return Attribute::get(fn () => $this->brand_name);
    }

    protected function generic(): Attribute
    {
        return Attribute::get(fn () => $this->generic_name);
    }

    protected function form(): Attribute
    {
        return Attribute::get(fn () => $this->dosage_form);
    }
}
