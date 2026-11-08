<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineAlias extends Model
{
    public $timestamps = false;

    protected $fillable = ['medicine_id', 'alias'];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
