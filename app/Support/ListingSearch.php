<?php

namespace App\Support;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Http\Requests\MedicineRequestRequest;
use App\Models\Location;
use App\Models\MedicineListing;
use App\Services\MatchingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Keyword search + filters over public listings (database query).
 * Blocked, draft, paused, unreviewed, expired listings and listings of
 * suspended members can never appear: the query starts from publiclyVisible().
 * Semantic (AI) ranking is added on top of these hard filters in Part 3.
 */
class ListingSearch
{
    public const PER_PAGE = 9;

    public const SORTS = [
        'relevance' => 'Most relevant',
        'newest' => 'Newest first',
        'nearest' => 'Nearest to me',
        'expiry' => 'Latest expiry',
    ];

    /** Read and sanitise the filters from the query string. Unknown values are ignored, never trusted. */
    public static function filtersFrom(Request $request): array
    {
        $tree = Location::tree();
        $division = array_key_exists((string) $request->query('division'), $tree) ? $request->query('division') : '';
        $district = $division && isset($tree[$division][$request->query('district')]) ? $request->query('district') : '';
        $upazila = $district && in_array($request->query('upazila'), $tree[$division][$district], true) ? $request->query('upazila') : '';

        return [
            'q' => Str::limit(trim((string) $request->query('q', '')), 100, ''),
            'division' => $division,
            'district' => $district,
            'upazila' => $upazila,
            'type' => array_values(array_intersect((array) $request->query('type', []), array_column(ListingType::cases(), 'value'))),
            'form' => in_array($request->query('form'), MedicineRequestRequest::FORMS, true) ? $request->query('form') : '',
            'strength' => Str::limit(trim((string) $request->query('strength', '')), 30, ''),
            'availability' => $request->query('availability') === 'all' ? 'all' : 'available',
            'sort' => array_key_exists((string) $request->query('sort'), self::SORTS) ? $request->query('sort') : 'relevance',
        ];
    }

    public static function run(array $f, Request $request): LengthAwarePaginator
    {
        $query = MedicineListing::query()->publiclyVisible()->withCardData()
            ->select('medicine_listings.*')
            ->join('medicines', 'medicines.id', '=', 'medicine_listings.medicine_id')
            ->join('locations', 'locations.id', '=', 'medicine_listings.location_id');

        $terms = self::terms($f['q']);
        if ($terms) {
            // Every word must appear in the brand, generic name, strength, form or a catalogue alias.
            foreach ($terms as $term) {
                $like = '%'.$term.'%';
                $query->where(fn (Builder $q) => $q
                    ->where('medicines.brand_name', 'like', $like)
                    ->orWhere('medicines.generic_name', 'like', $like)
                    ->orWhereRaw("REPLACE(LOWER(medicines.strength), ' ', '') like ?", [str_replace(' ', '', $like)])
                    ->orWhere('medicines.dosage_form', 'like', $like)
                    ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('medicine_aliases')
                        ->whereColumn('medicine_aliases.medicine_id', 'medicines.id')->where('alias', 'like', $like)));
            }
        }

        $query->when($f['division'], fn ($q) => $q->where('locations.division', $f['division']))
            ->when($f['district'], fn ($q) => $q->where('locations.district', $f['district']))
            ->when($f['upazila'], fn ($q) => $q->where('locations.upazila', $f['upazila']))
            ->when($f['type'], fn ($q) => $q->whereIn('medicine_listings.type', $f['type']))
            ->when($f['form'], fn ($q) => $q->where('medicines.dosage_form', $f['form']))
            ->when($f['strength'], fn ($q) => $q->whereRaw("REPLACE(LOWER(medicines.strength), ' ', '') like ?", ['%'.str_replace(' ', '', Str::lower($f['strength'])).'%']))
            ->when($f['availability'] === 'available', fn ($q) => $q->where('medicine_listings.status', ListingStatus::Available));

        $home = $request->user()?->location;
        match ($f['sort']) {
            'newest' => $query->orderByDesc('medicine_listings.published_at'),
            'expiry' => $query->orderByDesc('medicine_listings.expires_on'),
            'nearest' => $home
                ? $query->orderByRaw('CASE WHEN locations.upazila = ? AND locations.district = ? THEN 0 WHEN locations.district = ? THEN 1 WHEN locations.division = ? THEN 2 ELSE 3 END', [$home->upazila, $home->district, $home->district, $home->division])
                : $query->orderBy('locations.division')->orderBy('locations.district'),
            default => $terms
                // Exact brand first, then brand prefix, then generic, then everything else.
                ? $query->orderByRaw('CASE WHEN LOWER(medicines.brand_name) = ? THEN 0 WHEN LOWER(medicines.brand_name) LIKE ? THEN 1 WHEN LOWER(medicines.generic_name) = ? THEN 2 ELSE 3 END', [Str::lower($f['q']), Str::lower($terms[0]).'%', Str::lower($f['q'])])
                : $query->orderByDesc('medicine_listings.published_at'),
        };

        return $query->orderByDesc('medicine_listings.id')->paginate(self::PER_PAGE)->withQueryString();
    }

    /** Split the query into normalised words ("500 mg" -> "500mg"). */
    private static function terms(string $q): array
    {
        return array_values(array_filter(explode(' ', MatchingService::norm($q)), fn ($t) => mb_strlen($t) >= 2));
    }
}
