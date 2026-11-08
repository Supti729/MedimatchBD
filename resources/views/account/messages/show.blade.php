@php $c = $active; @endphp
<x-layouts.app :title="'Chat with '.$c->other->name">
    <div class="card grid overflow-hidden lg:h-[calc(100vh-9rem)] lg:grid-cols-[20rem_1fr]">
        <div class="hidden overflow-y-auto border-r border-line lg:block">@include('account.messages._list')</div>

        <section class="flex min-h-[70vh] flex-col lg:min-h-0" aria-label="Conversation with {{ $c->other->name }}">
            <header class="flex items-center gap-3 border-b border-line px-4 py-3">
                <a href="{{ route('messages.index') }}" class="grid size-10 place-items-center rounded-lg hover:bg-canvas lg:hidden"><x-icon name="arrow-left" /><span class="sr-only">All conversations</span></a>
                <x-avatar :user="$c->other" />
                <div class="min-w-0 flex-1">
                    <p class="font-bold">{{ $c->other->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $isDonor ? 'Your listing' : 'About listing' }}: <a class="font-semibold text-brand-700 hover:underline" href="{{ $isDonor ? route('listings.index') : route('medicines.show', $c->listing) }}">{{ $c->listing->name }} · {{ $c->listing->quantity_label }}</a></p>
                </div>
                @if ($c->status)<span class="hidden sm:block"><x-status-badge :status="$c->status" /></span>@endif
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-trigger class="grid size-10 place-items-center rounded-lg hover:bg-canvas" aria-haspopup="true"><x-icon name="more" /><span class="sr-only">Conversation options</span></button>
                    <div data-dropdown-panel hidden class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-pop">
                        @if ($c->interaction && Gate::allows('cancel', $c->interaction))
                            <button type="button" data-dialog-open="cancel-handover" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm hover:bg-canvas"><x-icon name="x" class="size-4" />Cancel hand-over</button>
                        @endif
                        <a href="{{ route('members.show', $c->other->id) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-canvas"><x-icon name="user" class="size-4" />View profile</a>
                        <button type="button" data-dialog-open="report-chat" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-700 hover:bg-red-50"><x-icon name="flag" class="size-4" />Report conversation</button>
                    </div>
                </div>
            </header>

            @php $i = $c->interaction; $IS = \App\Enums\InteractionStatus::class; @endphp
            @if ($i && $i->status->isOpen())
                <div class="flex flex-wrap items-center gap-3 border-b border-line bg-brand-50 px-4 py-3 text-sm">
                    <x-icon name="info" class="size-5 text-brand-700" />
                    <p class="min-w-0 flex-1">
                        @if ($i->status === $IS::Requested)
                            {{ $isDonor ? $c->other->name.' requested this medicine. Reserve it if you can help.' : 'Request sent. Waiting for '.$c->other->name.' to reserve it for you.' }}
                            @if ($isDonor && ! $requesterCleared)<span class="block text-xs text-warm-700">This medicine needs a verified prescription check for {{ $c->other->name }} before you can reserve it. You'll only see whether it passed.</span>@endif
                        @else
                            Reserved for {{ $isDonor ? $c->other->name : 'you' }}. After the hand-over, either of you can mark it complete.
                        @endif
                    </p>
                    @if (Gate::allows('reserve', $i))<x-button size="sm" icon="bookmark" data-dialog-open="reserve" :disabled="! $requesterCleared">Reserve</x-button>@endif
                    @if (Gate::allows('complete', $i))<x-button size="sm" icon="check" data-dialog-open="complete">Mark complete</x-button>@endif
                </div>
            @elseif (! $i && ! $isDonor && in_array($c->listing->status, [\App\Enums\ListingStatus::Available, \App\Enums\ListingStatus::Requested], true))
                <form method="POST" action="{{ route('medicines.request', $c->listing) }}" class="flex flex-wrap items-center gap-3 border-b border-line bg-brand-50 px-4 py-3 text-sm">
                    @csrf
                    <p class="min-w-0 flex-1">Ready to ask for this medicine?</p>
                    <x-button type="submit" size="sm" icon="inbox">Request it</x-button>
                </form>
            @elseif ($i && $i->status === $IS::Completed)
                <p class="border-b border-line bg-canvas px-4 py-3 text-sm text-muted">This hand-over was completed on {{ $i->completed_at->format('d M Y') }}.</p>
            @endif

            <div class="flex-1 space-y-3 overflow-y-auto bg-canvas px-4 py-5" data-scroll-bottom aria-live="polite">
                <p class="mx-auto max-w-sm rounded-lg bg-white px-3 py-2 text-center text-xs text-muted ring-1 ring-line"><x-icon name="lock" class="mr-1 inline size-3.5" />Keep chat on Medimatch BD. Don't share phone numbers, addresses or ID documents. Meet in a public place.</p>
                @if ($messages->isEmpty())<p class="py-6 text-center text-sm text-muted">No messages yet. Say hello and agree a safe place to meet.</p>@endif
                @foreach ($messages as $m)
                    <div @class(['flex', 'justify-end' => $m->mine])>
                        <div @class(['max-w-[80%] rounded-2xl px-4 py-2.5 text-sm shadow-card', 'rounded-br-sm bg-brand-600 text-white' => $m->mine, 'rounded-bl-sm bg-white text-ink' => ! $m->mine])>
                            <p>{{ $m->body }}</p>
                            <p @class(['mt-1 text-right text-[11px]', 'text-brand-100' => $m->mine, 'text-muted' => ! $m->mine])>{{ $m->sent_at->format('d M, g:i a') }}@if ($m->mine) · {{ $m->is_read ? 'Read' : 'Sent' }}@endif</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('body')<p class="border-t border-line bg-red-50 px-4 py-2 text-sm font-medium text-red-800" role="alert">{{ $message }}</p>@enderror
            @can('send', $c)
            <form method="POST" action="{{ route('messages.send', $c->id) }}" data-loading class="flex items-end gap-2 border-t border-line bg-white p-3">
                @csrf
                <button type="button" disabled title="Attachments are restricted" class="grid size-11 shrink-0 place-items-center rounded-lg text-muted opacity-60"><x-icon name="paperclip" /><span class="sr-only">Attachments are not allowed in chat</span></button>
                <label for="message-body" class="sr-only">Message</label>
                <textarea id="message-body" name="body" rows="1" required maxlength="1000" placeholder="Write a message…" class="field mt-0 max-h-32 min-h-11 resize-y">{{ old('body') }}</textarea>
                <x-button type="submit" icon="send" data-loading-text="Sending">Send</x-button>
            </form>
            @else
            <p class="border-t border-line bg-canvas p-4 text-center text-sm text-muted">You can no longer send messages in this conversation.</p>
            @endcan
        </section>
    </div>

    @if ($i)
        <x-confirm-dialog id="reserve" title="Reserve for {{ $c->other->name }}?" :action="route('interactions.update', $i)" method="PATCH" confirm="Reserve">The medicine is held for this member and other requesters wait until the hand-over is complete or cancelled.<input type="hidden" name="action" value="reserve"></x-confirm-dialog>
        <x-confirm-dialog id="complete" title="Mark hand-over complete?" :action="route('interactions.update', $i)" method="PATCH" confirm="Mark complete">Only confirm once the medicine has been handed over and checked. The listing will close.<input type="hidden" name="action" value="complete"></x-confirm-dialog>
        <x-confirm-dialog id="cancel-handover" title="Cancel this hand-over?" :action="route('interactions.update', $i)" method="PATCH" confirm="Cancel hand-over" variant="danger">{{ $c->other->name }} will be notified.<input type="hidden" name="action" value="cancel"></x-confirm-dialog>
    @endif
    <x-confirm-dialog id="report-chat" title="Report this conversation" :action="route('reports.store')" confirm="Send report" variant="danger">
        Moderators can read this conversation only because it is reported.
        <input type="hidden" name="conversation_id" value="{{ $c->id }}">
        <label for="chat-report-reason" class="label mt-4">Reason</label>
        <select id="chat-report-reason" name="category" required class="field"><option value="">Choose a reason</option>@foreach (\App\Models\Report::CATEGORIES as $category)<option>{{ $category }}</option>@endforeach</select>
        <label for="chat-report-details" class="label mt-3">What happened?</label>
        <textarea id="chat-report-details" name="details" rows="3" maxlength="1000" class="field"></textarea>
    </x-confirm-dialog>
</x-layouts.app>
