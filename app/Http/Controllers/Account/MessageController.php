<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\MessageService;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Private, database-backed conversations. Only the two participants can open one. */
class MessageController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.messages.index', ['conversations' => $this->conversationsFor($request), 'active' => null]);
    }

    public function show(Request $request, Conversation $conversation, VerificationService $verification): View
    {
        Gate::authorize('view', $conversation);
        $conversation->load(['listing.medicine', 'listing.location', 'donor', 'requester', 'interaction.medicineRequest', 'messages']);
        $me = $request->user();
        $other = $conversation->otherParticipant($me);
        $otherReadAt = $conversation->readAtFor($other);
        $conversation->markReadFor($me);

        return view('account.messages.show', [
            'conversations' => $this->conversationsFor($request),
            'active' => $conversation,
            'messages' => $conversation->messages->map(fn ($m) => (object) [
                'mine' => $m->sender_id === $me->id,
                'body' => $m->body,
                'sent_at' => $m->created_at,
                'is_read' => $otherReadAt && $m->created_at->lte($otherReadAt),
            ]),
            'isDonor' => $me->id === $conversation->donor_id,
            'requesterCleared' => $verification->requesterCleared($conversation->requester, $conversation->listing->medicine),
        ]);
    }

    public function send(Request $request, Conversation $conversation, MessageService $messages): RedirectResponse
    {
        Gate::authorize('send', $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $messages->send($conversation, $request->user(), $data['body']);

        return redirect()->route('messages.show', $conversation);
    }

    private function conversationsFor(Request $request)
    {
        $me = $request->user();

        return Conversation::forUser($me)
            ->with(['listing.medicine', 'donor', 'requester', 'latestMessage', 'interaction'])
            ->orderByDesc('last_message_at')->orderByDesc('updated_at')
            ->get()
            ->each(function ($c) use ($me) {
                $c->setAttribute('unread', $c->unreadCountFor($me));
                $c->setAttribute('last_message', $c->latestMessage?->body ?? 'No messages yet');
            });
    }
}
