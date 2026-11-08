<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Platform-wide settings managed by administrators. */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public const DEFAULTS = [
        'review_new_members' => true,        // review listings from members younger than 30 days
        'review_verification_required' => true,
        'allow_affordable_access' => false,  // only for approved partner programmes
        'min_expiry_months' => 1,
        'request_expiry_days' => 30,
        'ai_matching' => false,              // Part 3
    ];

    public static function get(string $key): mixed
    {
        $all = cache()->rememberForever('settings', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        cache()->forget('settings');
    }
}
