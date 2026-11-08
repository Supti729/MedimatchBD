<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['division', 'district', 'upazila'];

    public static function findFor(?string $division, ?string $district, ?string $upazila): ?self
    {
        return static::where(compact('division', 'district', 'upazila'))->first();
    }

    public function label(): string
    {
        return "{$this->upazila}, {$this->district}";
    }

    /** Division => District => [Upazila] tree for the cascading selects. */
    public static function tree(): array
    {
        return cache()->rememberForever('locations.tree', function () {
            $tree = [];
            foreach (static::orderBy('division')->orderBy('district')->orderBy('upazila')->get() as $l) {
                $tree[$l->division][$l->district][] = $l->upazila;
            }

            return $tree;
        });
    }
}
