<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleData;
use Illuminate\View\View;

/** Private conversations. Part 2 enforces that only participants can open a conversation. */
class MessageController extends Controller
{
    public function index(): View
    {
        return view('account.messages.index', ['conversations' => SampleData::conversations()->sortByDesc('updated_at'), 'active' => null]);
    }

    public function show(int $conversation): View
    {
        $conversation = SampleData::conversation($conversation);
        abort_unless($conversation, 404);

        return view('account.messages.show', [
            'conversations' => SampleData::conversations()->sortByDesc('updated_at'),
            'active' => $conversation,
            'messages' => SampleData::messages($conversation->id),
        ]);
    }
}
