{{-- Conversation list (shared by index and conversation pages). --}}
@if ($conversations->isEmpty())
    <div class="p-6"><x-empty-state title="No conversations yet" icon="message">Conversations start when you request a medicine or someone requests yours.<x-slot:actions><x-button :href="route('medicines.index')" icon="search" size="sm">Find Medicine</x-button></x-slot:actions></x-empty-state></div>
@endif
<ul class="divide-y divide-line">
    @foreach ($conversations as $c)
        @php $isActive = $active?->id === $c->id; @endphp
        <li>
            <a href="{{ route('messages.show', $c->id) }}" @class(['flex gap-3 px-4 py-3.5', 'bg-brand-50' => $isActive, 'hover:bg-canvas' => ! $isActive]) @if ($isActive) aria-current="page" @endif>
                <x-avatar :user="$c->other" />
                <span class="min-w-0 flex-1">
                    <span class="flex items-baseline justify-between gap-2"><span @class(['truncate', 'font-bold' => $c->unread, 'font-semibold' => ! $c->unread])>{{ $c->other->name }}</span><span class="shrink-0 text-xs text-muted">{{ ($c->last_message_at ?? $c->updated_at)->diffForHumans(short: true) }}</span></span>
                    <span class="block truncate text-xs font-semibold text-brand-700">{{ $c->donor_id === auth()->id() ? 'Your listing' : 'Listing' }}: {{ $c->listing->name }}</span>
                    <span class="flex items-center gap-2"><span @class(['block flex-1 truncate text-sm', 'text-ink' => $c->unread, 'text-muted' => ! $c->unread])>{{ $c->last_message }}</span>@if ($c->unread)<span class="rounded-full bg-brand-600 px-2 text-xs font-bold text-white">{{ $c->unread }}<span class="sr-only"> unread</span></span>@endif</span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
