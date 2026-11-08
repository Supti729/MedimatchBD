<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\MedicineListing;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $status = ListingStatus::tryFrom((string) $request->query('status'));

        return view('admin.listings.index', [
            'listings' => MedicineListing::with(['medicine', 'owner', 'location'])->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(MedicineListing $listing): View
    {
        return view('admin.listings.show', [
            'listing' => $listing->load(['medicine', 'owner', 'location', 'images', 'reviewer']),
            'reports' => $listing->reports()->latest()->get(),
        ]);
    }

    public function decide(Request $request, MedicineListing $listing, ListingService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'block'])],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $service->moderate($listing, $data['decision'], $data['reason']);

        return back()->with('success', 'Decision recorded and the owner was notified.');
    }
}
