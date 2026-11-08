<?php

namespace App\Http\Controllers\Account;

use App\Enums\InteractionStatus;
use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\RequestMatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $me = $request->user();

        $stats = [
            'activeListings' => $me->listings()->whereIn('status', [ListingStatus::Available, ListingStatus::Requested])->count(),
            'activeRequests' => $me->medicineRequests()->whereIn('status', [RequestStatus::Active, RequestStatus::Matching, RequestStatus::MatchFound])->count(),
            'pendingVerification' => $me->verificationSubmissions()->whereIn('status', [VerificationStatus::Submitted, VerificationStatus::UnderReview])->count()
                + $me->listings()->where('status', ListingStatus::PendingReview)->count(),
            'reserved' => Interaction::involving($me)->where('status', InteractionStatus::Reserved)->count(),
            'completed' => Interaction::involving($me)->where('status', InteractionStatus::Completed)->count(),
        ];

        // Best current matches across the member's open requests (still-available listings only).
        $matches = RequestMatch::with(['request', 'listing.medicine', 'listing.location', 'listing.owner'])
            ->whereHas('request', fn ($q) => $q->where('user_id', $me->id)->whereIn('status', RequestStatus::open()))
            ->whereHas('listing', fn ($q) => $q->publiclyVisible()->where('status', ListingStatus::Available))
            ->orderByDesc('score')->limit(3)->get()
            ->map(fn ($m) => (object) ['request' => $m->request, 'listing' => $m->listing, 'relevance' => $m->score]);

        return view('account.dashboard', [
            'me' => $me,
            'stats' => $stats,
            'matches' => $matches,
            'activity' => $me->notifications()->latest()->limit(5)->get(),
            'requests' => $me->medicineRequests()->latest()->limit(4)->get(),
        ]);
    }
}
