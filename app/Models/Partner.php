<?php

namespace App\Models;

use App\Enums\PartnerStatus;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    public const TYPES = ['Clinic', 'Hospital', 'NGO', 'Pharmacy', 'CSR sponsor'];

    protected $fillable = ['name', 'type', 'district', 'plan', 'status', 'contact_email', 'notes'];

    protected function casts(): array
    {
        return ['status' => PartnerStatus::class];
    }
}
