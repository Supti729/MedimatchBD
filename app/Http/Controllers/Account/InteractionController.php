<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Services\InteractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Reserve, complete or cancel a hand-over. Who may do what is decided by InteractionPolicy. */
class InteractionController extends Controller
{
    public function update(Request $request, Interaction $interaction, InteractionService $service): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', Rule::in(['reserve', 'complete', 'cancel'])]])['action'];
        Gate::authorize($action, $interaction);

        match ($action) {
            'reserve' => $service->reserve($interaction),
            'complete' => $service->complete($interaction, $request->user()),
            'cancel' => $service->cancel($interaction, $request->user()),
        };

        return back()->with('success', match ($action) {
            'reserve' => 'Reserved. Arrange a safe, public hand-over in the chat.',
            'complete' => 'Hand-over marked as completed. Thank you!',
            'cancel' => 'The hand-over was cancelled.',
        });
    }
}
