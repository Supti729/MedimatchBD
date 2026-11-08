<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $me = SampleData::currentUser();
        $listings = SampleData::listings()->filter(fn ($l) => $l->owner->id === $me->id);
        $requests = SampleData::requests()->filter(fn ($r) => $r->owner->id === $me->id);

        // Counted from records, exactly as Part 2 will count from the database.
        $stats = [
            'activeListings' => $listings->whereIn('status', [ListingStatus::Available, ListingStatus::Requested])->count(),
            'activeRequests' => $requests->whereIn('status', [RequestStatus::Active, RequestStatus::Matching, RequestStatus::MatchFound])->count(),
            'pendingVerification' => SampleData::verifications()->filter(fn ($v) => $v->user->id === $me->id && in_array($v->status, [VerificationStatus::Submitted, VerificationStatus::UnderReview], true))->count()
                + $listings->where('status', ListingStatus::PendingReview)->count(),
            'reserved' => $listings->where('status', ListingStatus::Reserved)->count() + $requests->where('status', RequestStatus::Reserved)->count(),
            'completed' => $listings->where('status', ListingStatus::Completed)->count() + $requests->where('status', RequestStatus::Completed)->count(),
        ];

        return view('account.dashboard', [
            'me' => $me,
            'stats' => $stats,
            'matches' => $requests->flatMap(fn ($r) => $r->matches->map(fn ($m) => (object) ['request' => $r, 'listing' => $m->listing, 'relevance' => $m->relevance]))->sortByDesc('relevance')->take(3),
            'activity' => SampleData::notifications()->take(5),
            'requests' => $requests->take(3),
        ]);
    }
}
