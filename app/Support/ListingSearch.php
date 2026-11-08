<?php

namespace App\Support;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Keyword search + filters for public listings.
 *
 * PART 1: runs over SampleData collections so the browse page is genuinely
 * interactive. PART 2 rewrites run() as an Eloquent query with the same
 * filter keys, so the view and URLs stay unchanged. The AI ranking in Part 3
 * plugs in as an extra sort option on top of these hard filters.
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
        $locations = config('locations');
        $division = in_array($request->query('division'), array_keys($locations), true) ? $request->query('division') : '';
        $district = $division && isset($locations[$division][$request->query('district')]) ? $request->query('district') : '';
        $upazila = $district && in_array($request->query('upazila'), $locations[$division][$district], true) ? $request->query('upazila') : '';

        $types = array_values(array_intersect((array) $request->query('type', []), array_column(ListingType::cases(), 'value')));

        return [
            'q' => Str::limit(trim((string) $request->query('q', '')), 100, ''),
            'division' => $division,
            'district' => $district,
            'upazila' => $upazila,
            'type' => $types,
            'form' => in_array($request->query('form'), SampleData::dosageForms(), true) ? $request->query('form') : '',
            'strength' => Str::limit(trim((string) $request->query('strength', '')), 30, ''),
            'availability' => $request->query('availability') === 'all' ? 'all' : 'available',
            'sort' => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'relevance',
        ];
    }

    public static function run(array $f, Request $request): LengthAwarePaginator
    {
        $needle = self::normalise($f['q']);
        $home = SampleData::currentUser();

        $results = SampleData::publicListings()
            ->when($needle !== '', fn ($c) => $c->filter(fn ($l) => self::score($l, $needle) > 0))
            ->when($f['division'], fn ($c) => $c->where('division', $f['division']))
            ->when($f['district'], fn ($c) => $c->where('district', $f['district']))
            ->when($f['upazila'], fn ($c) => $c->where('upazila', $f['upazila']))
            ->when($f['type'], fn ($c) => $c->filter(fn ($l) => in_array($l->type->value, $f['type'], true)))
            ->when($f['form'], fn ($c) => $c->where('form', $f['form']))
            ->when($f['strength'], fn ($c) => $c->filter(fn ($l) => str_contains(self::normalise($l->strength), self::normalise($f['strength']))))
            ->when($f['availability'] === 'available', fn ($c) => $c->where('status', ListingStatus::Available));

        $results = match ($f['sort']) {
            'newest' => $results->sortByDesc('created_at'),
            'expiry' => $results->sortByDesc('expires_at'),
            'nearest' => $results->sortBy(fn ($l) => [$l->district !== $home->district, $l->division !== $home->division, $l->id]),
            default => $results->sortBy(fn ($l) => [-self::score($l, $needle), -$l->created_at->timestamp]),
        };

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $results->forPage($page, self::PER_PAGE)->values(),
            $results->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /** Simple keyword score: brand/generic/alias containment. (Semantic matching is Part 3.) */
    private static function score(object $listing, string $needle): int
    {
        if ($needle === '') {
            return 1;
        }
        $brand = self::normalise($listing->name);
        $generic = self::normalise($listing->generic);
        $aliases = array_map(self::normalise(...), $listing->medicine->aliases);
        $full = self::normalise("{$listing->name} {$listing->generic} {$listing->strength} {$listing->form}");

        return match (true) {
            $brand === $needle => 100,
            str_starts_with($brand, $needle) => 80,
            $generic === $needle || in_array($needle, $aliases, true) => 70,
            str_contains($full, $needle) => 50,
            collect($aliases)->contains(fn ($a) => str_contains($a, $needle)) => 40,
            // every word of the query appears somewhere (e.g. "paracetamol 500 tablet")
            collect(explode(' ', $needle))->every(fn ($w) => $w !== '' && str_contains($full.' '.implode(' ', $aliases), $w)) => 30,
            default => 0,
        };
    }

    private static function normalise(string $text): string
    {
        $text = Str::lower($text);
        $text = preg_replace('/(\d)\s*(mg|ml|mcg|iu|g)\b/', '$1$2', $text); // "500 mg" == "500mg"
        $text = preg_replace('/[^\p{L}\p{N}\/. ]+/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
