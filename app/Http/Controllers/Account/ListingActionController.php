<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Services\InteractionService;
use App\Services\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Actions a member takes on someone else's listing: request it, chat, save. */
class ListingActionController extends Controller
{
    public function __construct(private InteractionService $interactions) {}

    public function request(Request $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('request', $listing);
        $data = $request->validate([
            'medicine_request_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $linked = isset($data['medicine_request_id']) ? MedicineRequest::where('user_id', $request->user()->id)->find($data['medicine_request_id']) : null;

        $interaction = $this->interactions->request($listing, $request->user(), $linked, $data['message'] ?? null);

        return redirect()->route('messages.show', $interaction->conversation_id)
            ->with('success', "Request sent to {$listing->owner->name}. You'll be notified when they reply or reserve it for you.");
    }

    public function chat(Request $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('request', $listing);
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $existing = $listing->conversations()->where('requester_id', $request->user()->id)->exists();
        if (! $existing && $listing->owner->privacySetting('who_can_message') === 'none') {
            return back()->with('warning', "{$listing->owner->name} only accepts formal requests. Use “Request This Medicine” instead.");
        }

        $conversation = $this->interactions->conversationFor($listing, $request->user());
        app(MessageService::class)->send($conversation, $request->user(), $data['message']);

        return redirect()->route('messages.show', $conversation);
    }

    public function save(Request $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('save', $listing);
        $request->user()->savedListings()->syncWithoutDetaching([$listing->id => ['created_at' => now()]]);

        return back()->with('success', 'Saved. Find it under Saved Medicines.');
    }

    public function unsave(Request $request, MedicineListing $listing): RedirectResponse
    {
        $request->user()->savedListings()->detach($listing->id);

        return back()->with('success', 'Removed from saved medicines.');
    }
}
