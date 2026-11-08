<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingStatus;
use App\Enums\MedicineEligibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineRequestRequest;
use App\Models\Medicine;
use App\Models\MedicineRequest;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** The member's own medicine requests. Ownership is checked by MedicineRequestPolicy. */
class RequestController extends Controller
{
    public function __construct(private RequestService $service) {}

    public function index(Request $request): View
    {
        return view('account.requests.index', [
            'requests' => $request->user()->medicineRequests()->with('location')->withCount('matches')->latest()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('account.requests.form', ['medicineRequest' => null, 'catalogue' => $this->catalogue(), 'me' => $request->user()->load('location')]);
    }

    public function store(MedicineRequestRequest $request): RedirectResponse
    {
        $medicineRequest = $this->service->create($request->user(), $request->requestData());
        $count = $medicineRequest->matches()->count();

        return redirect()->route('requests.show', $medicineRequest)->with('success', $count
            ? "Request posted. We found {$count} matching ".str('listing')->plural($count).'.'
            : 'Request posted. We\'ll notify you when a matching listing appears.');
    }

    public function show(MedicineRequest $medicineRequest): View
    {
        Gate::authorize('view', $medicineRequest);
        $medicineRequest->load(['location', 'medicine', 'suggestedMedicine', 'verificationSubmissions']);

        return view('account.requests.show', [
            'medicineRequest' => $medicineRequest,
            // Only matches whose listing is still publicly available.
            'matches' => $medicineRequest->matches()->with(['listing.medicine', 'listing.location', 'listing.owner'])
                ->whereHas('listing', fn ($q) => $q->publiclyVisible()->whereIn('status', [ListingStatus::Available, ListingStatus::Requested]))->get(),
            'needsPrescription' => $medicineRequest->medicine?->eligibility === MedicineEligibility::VerificationRequired,
        ]);
    }

    public function edit(Request $request, MedicineRequest $medicineRequest): View
    {
        Gate::authorize('update', $medicineRequest);

        return view('account.requests.form', ['medicineRequest' => $medicineRequest->load('location'), 'catalogue' => $this->catalogue(), 'me' => $request->user()]);
    }

    public function update(MedicineRequestRequest $request, MedicineRequest $medicineRequest): RedirectResponse
    {
        Gate::authorize('update', $medicineRequest);
        $this->service->update($medicineRequest, $request->requestData());

        return redirect()->route('requests.show', $medicineRequest)->with('success', 'Request updated. Matches were refreshed.');
    }

    public function cancel(MedicineRequest $medicineRequest): RedirectResponse
    {
        Gate::authorize('cancel', $medicineRequest);
        $this->service->cancel($medicineRequest);

        return redirect()->route('requests.index')->with('success', 'Request cancelled.');
    }

    private function catalogue()
    {
        return Medicine::where('is_active', true)->where('eligibility', '!=', MedicineEligibility::NotAllowed)->orderBy('brand_name')->get();
    }
}
