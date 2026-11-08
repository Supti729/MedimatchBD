<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\InteractionStatus;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\ReportStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Interaction;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Partner;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Every number on this page is counted from the database. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $isAdmin = auth()->user()->isAdmin();
        $pendingVerifications = VerificationSubmission::whereIn('status', [VerificationStatus::Submitted, VerificationStatus::UnderReview]);

        // Reviewers only see their own queue.
        if (! $isAdmin) {
            return view('admin.dashboard', [
                'isAdmin' => false,
                'stats' => ['Pending verification' => [$pendingVerifications->count(), 'shield']],
                'queue' => ['verifications' => (clone $pendingVerifications)->with('user')->oldest()->limit(8)->get()],
            ]);
        }

        $stats = [
            'Total users' => [User::where('role', 'user')->count(), 'users'],
            'Active users' => [User::where('role', 'user')->where('status', AccountStatus::Active)->where('last_active_at', '>=', now()->subDays(30))->count(), 'user'],
            'Total listings' => [MedicineListing::count(), 'pill'],
            'Active listings' => [MedicineListing::whereIn('status', ListingStatus::public())->count(), 'check'],
            'Total requests' => [MedicineRequest::count(), 'inbox'],
            'Pending verification' => [(clone $pendingVerifications)->count(), 'shield'],
            'Open reports' => [Report::whereIn('status', [ReportStatus::Open, ReportStatus::UnderReview, ReportStatus::Escalated])->count(), 'flag'],
            'Completed interactions' => [Interaction::where('status', InteractionStatus::Completed)->count(), 'history'],
            'Partner accounts' => [Partner::count(), 'building'],
        ];

        // Last 6 months of registrations, grouped in PHP so it works on MySQL and SQLite alike.
        $months = collect(range(5, 0))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonths($i)->format('M') => 0]);
        $registrations = User::where('role', 'user')->where('created_at', '>=', now()->startOfMonth()->subMonths(5))->pluck('created_at')
            ->countBy(fn (Carbon $d) => $d->format('M'));
        $completions = Interaction::where('status', InteractionStatus::Completed)->where('completed_at', '>=', now()->startOfMonth()->subMonths(5))->pluck('completed_at')
            ->countBy(fn (Carbon $d) => $d->format('M'));

        $byDistrict = fn ($table) => DB::table($table)->join('locations', 'locations.id', '=', "{$table}.location_id")
            ->select('locations.district', DB::raw('count(*) as total'))->groupBy('locations.district')->orderByDesc('total')->limit(6)->pluck('total', 'district')->all();

        return view('admin.dashboard', [
            'isAdmin' => true,
            'stats' => $stats,
            'registrations' => $months->merge($registrations->only($months->keys()))->all(),
            'completions' => $months->merge($completions->only($months->keys()))->all(),
            'listingsByDistrict' => $byDistrict('medicine_listings'),
            'requestsByDistrict' => $byDistrict('medicine_requests'),
            'byType' => collect(ListingType::cases())->mapWithKeys(fn ($t) => [$t->label() => MedicineListing::where('type', $t)->count()])->all(),
            'queue' => [
                'listings' => MedicineListing::with(['medicine', 'owner', 'location'])->where('status', ListingStatus::PendingReview)->oldest()->limit(6)->get(),
                'verifications' => (clone $pendingVerifications)->with('user')->oldest()->limit(6)->get(),
                'reports' => Report::with('reportable')->whereIn('status', [ReportStatus::Open, ReportStatus::Escalated])->oldest()->limit(6)->get(),
            ],
            'audit' => AuditLog::with('actor')->latest('created_at')->limit(6)->get(),
        ]);
    }
}
