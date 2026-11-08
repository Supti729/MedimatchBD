<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MedicineListing;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request, ReportService $service): RedirectResponse
    {
        $data = $request->validate([
            'listing_id' => ['nullable', 'integer', 'required_without_all:user_id,conversation_id'],
            'user_id' => ['nullable', 'integer'],
            'conversation_id' => ['nullable', 'integer'],
            'category' => ['required', Rule::in(Report::CATEGORIES)],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = match (true) {
            ! empty($data['listing_id']) => MedicineListing::findOrFail($data['listing_id']),
            ! empty($data['conversation_id']) => Conversation::findOrFail($data['conversation_id']),
            default => User::findOrFail($data['user_id']),
        };
        // You can only report a conversation you are part of, and a listing you can see.
        if ($target instanceof Conversation) {
            Gate::authorize('view', $target);
        }
        if ($target instanceof MedicineListing) {
            abort_unless(Gate::allows('view', $target), 404);
        }

        $service->create($request->user(), $target, $data['category'], $data['details'] ?? null);

        return back()->with('success', 'Thank you. Your report was sent to the moderators.');
    }
}
