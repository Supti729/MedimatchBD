<?php

namespace App\Http\Controllers;

use App\Support\ListingSearch;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public browsing, search and listing details. */
class MedicineController extends Controller
{
    public function index(Request $request): View
    {
        return $this->results($request, 'medicines.index');
    }

    public function search(Request $request): View
    {
        return $this->results($request, 'medicines.search');
    }

    public function show(int $listing): View
    {
        $listing = SampleData::listing($listing);

        // Only publicly visible listings can be opened (never drafts, blocked or unreviewed ones).
        abort_unless($listing && in_array($listing->status, SampleData::publicStatuses(), true), 404);

        $similar = SampleData::publicListings()
            ->filter(fn ($l) => $l->id !== $listing->id && $l->generic === $listing->generic)
            ->take(3);

        return view('medicines.show', compact('listing', 'similar'));
    }

    private function results(Request $request, string $route): View
    {
        $filters = ListingSearch::filtersFrom($request);
        $results = ListingSearch::run($filters, $request);

        return view('medicines.index', [
            'results' => $results,
            'filters' => $filters,
            'route' => $route,
            'isSearch' => $route === 'medicines.search',
            'layout' => $request->query('view') === 'list' ? 'list' : 'grid',
        ]);
    }
}
