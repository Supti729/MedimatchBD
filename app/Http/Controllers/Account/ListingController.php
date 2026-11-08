<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The member's own listings. Part 2 adds a ListingPolicy so only the owner can edit. */
class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $mine = SampleData::listings()->filter(fn ($l) => $l->owner->id === SampleData::CURRENT_USER_ID)->sortByDesc('created_at');
        $tab = $request->query('status', 'all');
        $groups = [
            'all' => null,
            'active' => [ListingStatus::Available, ListingStatus::Requested, ListingStatus::Reserved],
            'review' => [ListingStatus::Draft, ListingStatus::PendingReview],
            'closed' => [ListingStatus::Completed, ListingStatus::Cancelled, ListingStatus::Paused, ListingStatus::Rejected, ListingStatus::Blocked],
        ];
        $tab = array_key_exists($tab, $groups) ? $tab : 'all';

        return view('account.listings.index', [
            'listings' => $groups[$tab] ? $mine->filter(fn ($l) => in_array($l->status, $groups[$tab], true)) : $mine,
            'tab' => $tab,
            'counts' => collect($groups)->map(fn ($g) => $g ? $mine->filter(fn ($l) => in_array($l->status, $g, true))->count() : $mine->count()),
        ]);
    }

    public function create(): View
    {
        return view('account.listings.form', ['listing' => null, 'catalogue' => SampleData::catalogue()]);
    }

    public function edit(int $listing): View
    {
        $listing = SampleData::listing($listing);
        abort_unless($listing && $listing->owner->id === SampleData::CURRENT_USER_ID, 404);

        return view('account.listings.form', ['listing' => $listing, 'catalogue' => SampleData::catalogue()]);
    }
}
