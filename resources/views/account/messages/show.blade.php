@php $c = $active; @endphp
<x-layouts.app :title="'Chat with '.$c->other->name">
    <div class="card grid overflow-hidden lg:h-[calc(100vh-9rem)] lg:grid-cols-[20rem_1fr]">
        <div class="hidden overflow-y-auto border-r border-line lg:block">@include('account.messages._list')</div>

        <section class="flex min-h-[70vh] flex-col lg:min-h-0" aria-label="Conversation with {{ $c->other->name }}">
            <header class="flex items-center gap-3 border-b border-line px-4 py-3">
                <a href="{{ route('messages.index') }}" class="grid size-10 place-items-center rounded-lg hover:bg-canvas lg:hidden"><x-icon name="arrow-left" /><span class="sr-only">All conversations</span></a>
                <x-avatar :name="$c->other->name" />
                <div class="min-w-0 flex-1">
                    <p class="font-bold">{{ $c->other->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $c->subject_type === 'listing' ? 'About listing' : 'About request' }}: <a class="font-semibold text-brand-700 hover:underline" href="{{ $c->subject_type === 'listing' ? route('medicines.show', $c->subject->id) : route('requests.show', $c->subject->id) }}">{{ $c->subject->name }}</a></p>
                </div>
                <span class="hidden sm:block"><x-status-badge :status="$c->status" /></span>
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-trigger class="grid size-10 place-items-center rounded-lg hover:bg-canvas" aria-haspopup="true"><x-icon name="more" /><span class="sr-only">Conversation options</span></button>
                    <div data-dropdown-panel hidden class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-pop">
                        @if ($c->status === \App\Enums\InteractionStatus::Requested)
                            <button type="button" data-dialog-open="reserve" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm hover:bg-canvas"><x-icon name="bookmark" class="size-4" />Reserve for this member</button>
                        @endif
                        @if ($c->status === \App\Enums\InteractionStatus::Reserved)
                            <button type="button" data-dialog-open="complete" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm hover:bg-canvas"><x-icon name="check" class="size-4" />Mark hand-over complete</button>
                        @endif
                        <a href="{{ route('members.show', $c->other->id) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-canvas"><x-icon name="user" class="size-4" />View profile</a>
                        <button type="button" data-dialog-open="report-chat" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-700 hover:bg-red-50"><x-icon name="flag" class="size-4" />Report conversation</button>
                    </div>
                </div>
            </header>

            <div class="flex-1 space-y-3 overflow-y-auto bg-canvas px-4 py-5" data-scroll-bottom aria-live="polite">
                <p class="mx-auto max-w-sm rounded-lg bg-white px-3 py-2 text-center text-xs text-muted ring-1 ring-line"><x-icon name="lock" class="mr-1 inline size-3.5" />Keep chat on Medimatch BD. Don't share phone numbers, addresses or ID documents. Meet in a public place.</p>
                @foreach ($messages as $m)
                    <div @class(['flex', 'justify-end' => $m->mine])>
                        <div @class(['max-w-[80%] rounded-2xl px-4 py-2.5 text-sm shadow-card', 'rounded-br-sm bg-brand-600 text-white' => $m->mine, 'rounded-bl-sm bg-white text-ink' => ! $m->mine])>
                            <p>{{ $m->body }}</p>
                            <p @class(['mt-1 text-right text-[11px]', 'text-brand-100' => $m->mine, 'text-muted' => ! $m->mine])>{{ $m->sent_at->format('d M, g:i a') }}@if ($m->mine) · {{ $m->is_read ? 'Read' : 'Sent' }}@endif</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('messages.send', $c->id) }}" data-loading class="flex items-end gap-2 border-t border-line bg-white p-3">
                @csrf
                <button type="button" disabled title="Attachments are restricted" class="grid size-11 shrink-0 place-items-center rounded-lg text-muted opacity-60"><x-icon name="paperclip" /><span class="sr-only">Attachments are not allowed in chat</span></button>
                <label for="message-body" class="sr-only">Message</label>
                <textarea id="message-body" name="body" rows="1" required maxlength="1000" placeholder="Write a message…" class="field mt-0 max-h-32 min-h-11 resize-y"></textarea>
                <x-button type="submit" icon="send" data-loading-text="Sending">Send</x-button>
            </form>
        </section>
    </div>

    <x-confirm-dialog id="reserve" title="Reserve for {{ $c->other->name }}?" :action="route('interactions.update', $c->id)" method="PATCH" confirm="Reserve">The listing is held for this member and hidden from other requests until the hand-over is complete or cancelled.<input type="hidden" name="status" value="reserved"></x-confirm-dialog>
    <x-confirm-dialog id="complete" title="Mark hand-over complete?" :action="route('interactions.update', $c->id)" method="PATCH" confirm="Mark complete">Only confirm once the medicine has been handed over and checked.<input type="hidden" name="status" value="completed"></x-confirm-dialog>
    <x-confirm-dialog id="report-chat" title="Report this conversation" :action="route('reports.store')" confirm="Send report" variant="danger" reason reason-label="What happened?">Moderators can read this conversation only when it is reported.<input type="hidden" name="conversation_id" value="{{ $c->id }}"></x-confirm-dialog>
</x-layouts.app>
