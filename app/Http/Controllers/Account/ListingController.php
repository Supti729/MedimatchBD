<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListingRequest;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The member's own listings. Ownership is checked by MedicineListingPolicy. */
class ListingController extends Controller
{
    private const TABS = [
        'all' => null,
        'active' => [ListingStatus::Available, ListingStatus::Requested, ListingStatus::Reserved],
        'review' => [ListingStatus::Draft, ListingStatus::PendingReview],
        'closed' => [ListingStatus::Completed, ListingStatus::Cancelled, ListingStatus::Paused, ListingStatus::Rejected, ListingStatus::Blocked],
    ];

    public function __construct(private ListingService $service) {}

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? $request->query('status') : 'all';
        $mine = $request->user()->listings();

        return view('account.listings.index', [
            'listings' => (clone $mine)->with(['medicine', 'location'])->when(self::TABS[$tab], fn ($q, $s) => $q->whereIn('status', $s))->latest()->get(),
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn ($s) => (clone $mine)->when($s, fn ($q) => $q->whereIn('status', $s))->count()),
        ]);
    }

    public function create(): View
    {
        return view('account.listings.form', ['listing' => null, 'catalogue' => $this->catalogue()]);
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $listing = $this->service->create($request->user(), $request->listingData(), $request->file('photos', []), $request->isDraft());

        return redirect()->route('listings.index')->with('success', match ($listing->status) {
            ListingStatus::Draft => 'Draft saved. Only you can see it.',
            ListingStatus::PendingReview => 'Listing submitted. A reviewer will check it before it appears in search.',
            default => 'Your listing is live. Thank you for sharing!',
        });
    }

    public function edit(MedicineListing $listing): View
    {
        Gate::authorize('update', $listing);

        return view('account.listings.form', ['listing' => $listing->load('medicine', 'location', 'images'), 'catalogue' => $this->catalogue()]);
    }

    public function update(ListingRequest $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);
        $listing = $this->service->update($listing, $request->listingData(), $request->file('photos', []), $request->isDraft());

        return redirect()->route('listings.index')->with('success', $listing->status === ListingStatus::PendingReview
            ? 'Changes saved. The listing will be reviewed again before it goes live.'
            : 'Listing updated.');
    }

    public function status(Request $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('changeStatus', $listing);
        $data = $request->validate(['action' => ['required', Rule::in(['pause', 'reactivate', 'cancel'])]]);
        $this->service->ownerAction($listing, $data['action']);

        return back()->with('success', match ($data['action']) {
            'pause' => 'Listing paused. It is hidden from search.',
            'reactivate' => 'Listing is live again.',
            'cancel' => 'Listing cancelled.',
        });
    }

    private function catalogue()
    {
        return Medicine::where('is_active', true)->orderByRaw("CASE eligibility WHEN 'eligible' THEN 0 WHEN 'verification_required' THEN 1 ELSE 2 END")->orderBy('brand_name')->get();
    }
}
