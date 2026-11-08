<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\InteractionStatus;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /** role and status are deliberately NOT fillable: only admins change them, explicitly. */
    protected $fillable = ['name', 'email', 'phone', 'password', 'location_id', 'avatar_path', 'privacy'];

    protected $hidden = ['password', 'remember_token', 'verification_code'];

    public const DEFAULT_PRIVACY = [
        'show_photo' => true,
        'show_contributions' => true,
        'show_upazila' => true,
        'who_can_message' => 'matched',
        'notify_email' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'identity_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => AccountStatus::class,
            'privacy' => 'array',
        ];
    }

    /* ------------------------------------------------------------ Relations */

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MedicineListing::class);
    }

    public function medicineRequests(): HasMany
    {
        return $this->hasMany(MedicineRequest::class);
    }

    public function savedListings(): BelongsToMany
    {
        return $this->belongsToMany(MedicineListing::class, 'saved_listings')->withPivot('created_at');
    }

    public function verificationSubmissions(): HasMany
    {
        return $this->hasMany(VerificationSubmission::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Interaction::class, 'donor_id');
    }

    /* -------------------------------------------------------------- Roles */

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isReviewer(): bool
    {
        return in_array($this->role, [Role::Reviewer, Role::Admin], true);
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isVerifiedAccount(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::Suspended;
    }

    public function privacySetting(string $key): mixed
    {
        return ($this->privacy ?? [])[$key] ?? self::DEFAULT_PRIVACY[$key] ?? null;
    }

    /* ---------------------------------------- Display helpers used by views */

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

    protected function joinedAt(): Attribute
    {
        return Attribute::get(fn () => $this->created_at);
    }

    protected function isVerified(): Attribute
    {
        return Attribute::get(fn () => $this->identity_verified_at !== null);
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null);
    }

    /** Completed hand-overs as a donor. Uses a withCount(... as completed_count) value when preloaded. */
    protected function completedCount(): Attribute
    {
        return Attribute::get(fn ($value, $attributes) => (int) ($attributes['completed_count']
            ?? $this->donations()->where('status', InteractionStatus::Completed)->count()));
    }

    /** Phone shown to its owner/admins as +880 17XX-XXXXXX. */
    protected function phoneDisplay(): Attribute
    {
        return Attribute::get(fn () => $this->phone ? preg_replace('/^\+880(\d{4})(\d{6})$/', '+880 $1-$2', $this->phone) : null);
    }

    /** Normalise any accepted Bangladeshi mobile format to +8801XXXXXXXXX. */
    public static function normalisePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if (preg_match('/^(?:880|0)?(1[3-9]\d{8})$/', $digits, $m)) {
            return '+880'.$m[1];
        }

        return null;
    }
}
