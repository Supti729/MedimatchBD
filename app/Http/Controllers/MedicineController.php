<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\MedicineListing;
use App\Services\VerificationService;
use App\Support\ListingSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function show(Request $request, MedicineListing $listing, VerificationService $verification): View
    {
        // Drafts, blocked, paused or unreviewed listings are a plain 404 for everyone else.
        abort_unless(Gate::allows('view', $listing), 404);
        $listing->load(['medicine', 'location', 'owner', 'images']);
        $user = $request->user();

        return view('medicines.show', [
            'listing' => $listing,
            'similar' => MedicineListing::publiclyVisible()->withCardData()
                ->whereKeyNot($listing->id)
                ->whereHas('medicine', fn ($q) => $q->where('generic_name', $listing->generic))
                ->latest('published_at')->limit(3)->get(),
            'isSaved' => $user && $user->savedListings()->whereKey($listing->id)->exists(),
            'myRequests' => $user ? $user->medicineRequests()->whereIn('status', RequestStatus::open())->latest()->get() : collect(),
            'existingConversation' => $user ? $listing->conversations()->where('requester_id', $user->id)->first() : null,
            'needsVerification' => $user && ! $verification->requesterCleared($user, $listing->medicine),
        ]);
    }

    private function results(Request $request, string $route): View
    {
        $filters = ListingSearch::filtersFrom($request);
        $results = ListingSearch::run($filters, $request);

        // Nothing found by keywords? Ask the AI for close catalogue medicines (clearly labelled as suggestions).
        $suggestions = collect();
        if ($results->total() === 0 && $filters['q'] !== '') {
            $suggestions = ListingSearch::suggestMedicines($filters['q']);
            if ($suggestions->isNotEmpty()) {
                $results = ListingSearch::run([...$filters, 'q' => '', 'medicine_ids' => $suggestions->pluck('id')->all()], $request);
            }
        }

        return view('medicines.index', [
            'results' => $results,
            'suggestions' => $suggestions,
            'filters' => $filters,
            'route' => $route,
            'isSearch' => $route === 'medicines.search',
            'layout' => $request->query('view') === 'list' ? 'list' : 'grid',
        ]);
    }
}
