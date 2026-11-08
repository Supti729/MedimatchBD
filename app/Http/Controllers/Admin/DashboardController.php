<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\InteractionStatus;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\ReportStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $users = SampleData::users();
        $listings = SampleData::listings();
        $requests = SampleData::requests();

        // Every figure is counted from records (sample records in Part 1, the database in Part 2).
        $stats = [
            'Total users' => [$users->count(), 'users'],
            'Active users' => [$users->where('status', AccountStatus::Active)->count(), 'user'],
            'Total listings' => [$listings->count(), 'pill'],
            'Active listings' => [$listings->whereIn('status', [ListingStatus::Available, ListingStatus::Requested, ListingStatus::Reserved])->count(), 'check'],
            'Total requests' => [$requests->count(), 'inbox'],
            'Pending verification' => [SampleData::verifications()->whereIn('status', [VerificationStatus::Submitted, VerificationStatus::UnderReview])->count(), 'shield'],
            'Open reports' => [SampleData::reports()->whereIn('status', [ReportStatus::Open, ReportStatus::UnderReview, ReportStatus::Escalated])->count(), 'flag'],
            'Completed interactions' => [SampleData::interactions()->where('status', InteractionStatus::Completed)->count(), 'history'],
            'Partner accounts' => [SampleData::partners()->count(), 'building'],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'registrations' => $users->groupBy(fn ($u) => $u->joined_at->format('M'))->map->count()
                ->sortBy(fn ($c, $m) => \Illuminate\Support\Carbon::parse("1 $m")->month)->all(),
            'listingsByDistrict' => $listings->groupBy('district')->map->count()->sortDesc()->all(),
            'requestsByDistrict' => $requests->groupBy('district')->map->count()->sortDesc()->all(),
            'byType' => collect(ListingType::cases())->mapWithKeys(fn ($t) => [$t->label() => $listings->where('type', $t)->count()])->all(),
            'queue' => [
                'listings' => $listings->where('status', ListingStatus::PendingReview),
                'verifications' => SampleData::verifications()->whereIn('status', [VerificationStatus::Submitted, VerificationStatus::UnderReview]),
                'reports' => SampleData::reports()->whereIn('status', [ReportStatus::Open, ReportStatus::Escalated]),
            ],
            'openRequests' => $requests->whereIn('status', [RequestStatus::Active, RequestStatus::Matching])->count(),
            'audit' => SampleData::auditLogs()->take(5),
        ]);
    }
}
