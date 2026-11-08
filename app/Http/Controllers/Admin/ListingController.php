<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $status = ListingStatus::tryFrom((string) $request->query('status'));

        return view('admin.listings.index', [
            'listings' => SampleData::listings()->when($status, fn ($c) => $c->where('status', $status))->sortByDesc('created_at'),
            'status' => $status,
        ]);
    }

    public function show(int $listing): View
    {
        $listing = SampleData::listing($listing);
        abort_unless($listing, 404);

        return view('admin.listings.show', [
            'listing' => $listing,
            'reports' => SampleData::reports()->filter(fn ($r) => $r->listing?->id === $listing->id),
        ]);
    }
}
