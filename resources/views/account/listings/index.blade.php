<x-layouts.app title="My Listings">
    <x-page-header title="My listings" description="Medicine you are offering. You can pause, edit or cancel a listing at any time before it is completed.">
        <x-slot:actions><x-button :href="route('listings.create')" icon="plus">Create listing</x-button></x-slot:actions>
    </x-page-header>

    <nav aria-label="Filter listings" class="mb-5 flex gap-1 overflow-x-auto border-b border-line">
        @foreach (['all' => 'All', 'active' => 'Active', 'review' => 'Draft & review', 'closed' => 'Closed'] as $key => $label)
            <a href="{{ route('listings.index', $key === 'all' ? [] : ['status' => $key]) }}" @class(['-mb-px shrink-0 border-b-2 px-4 py-3 text-sm font-semibold', 'border-brand-600 text-brand-700' => $tab === $key, 'border-transparent text-muted hover:text-ink' => $tab !== $key]) @if ($tab === $key) aria-current="page" @endif>{{ $label }} <span class="ml-1 rounded-full bg-canvas px-2 text-xs">{{ $counts[$key] }}</span></a>
        @endforeach
    </nav>

    @if ($listings->isEmpty())
        <x-empty-state title="No listings here" icon="pill">When you list a medicine you can give, it shows up here.<x-slot:actions><x-button :href="route('listings.create')" icon="plus">Create listing</x-button></x-slot:actions></x-empty-state>
    @else
        <div class="space-y-3">
            @foreach ($listings as $listing)
                @php $s = $listing->status; $S = \App\Enums\ListingStatus::class; @endphp
                <article class="card flex flex-col gap-4 p-5 md:flex-row md:items-center">
                    <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="pill" class="size-6" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-bold">{{ $listing->name }}</h2><x-status-badge :status="$s" /><x-status-badge :status="$listing->type" size="xs" /></div>
                        <p class="text-sm text-muted">{{ $listing->strength }} {{ $listing->form }} · {{ $listing->quantity_label }} · {{ $listing->upazila }}, {{ $listing->district }} · Expires {{ $listing->expires_at->format('M Y') }}</p>
                        @if ($s === $S::PendingReview)<p class="mt-1 text-xs text-warm-700">A reviewer is checking this listing. It isn't visible in search yet.</p>@endif
                        @if ($s === $S::Draft)<p class="mt-1 text-xs text-muted">Draft: only you can see this.</p>@endif
                        @if (in_array($s, [$S::Rejected, $S::Blocked], true) && $listing->review_note)<p class="mt-1 text-xs text-red-700">Moderator note: {{ $listing->review_note }}</p>@endif
                        @if ($s === $S::Requested)<p class="mt-1 text-xs text-brand-700">Someone requested this. <a href="{{ route('messages.index') }}" class="font-semibold underline">Reply in Messages</a>.</p>@endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if (in_array($s, [$S::Available, $S::Requested, $S::Reserved], true))<x-button :href="route('medicines.show', $listing->id)" variant="neutral" size="sm" icon="eye">View</x-button>@endif
                        @if ($s->ownerCanEdit())<x-button :href="route('listings.edit', $listing->id)" variant="neutral" size="sm" icon="edit">Edit</x-button>@endif
                        @if ($s === $S::Available)<x-button variant="neutral" size="sm" icon="pause" data-dialog-open="pause-{{ $listing->id }}">Pause</x-button>@endif
                        @if ($s === $S::Paused)<x-button variant="neutral" size="sm" icon="play" data-dialog-open="pause-{{ $listing->id }}">Reactivate</x-button>@endif
                        @if ($s->ownerCanCancel())<x-button variant="danger-outline" size="sm" icon="x" data-dialog-open="cancel-{{ $listing->id }}">Cancel</x-button>@endif
                    </div>
                </article>
                @if (in_array($s, [$S::Available, $S::Paused], true))
                    <x-confirm-dialog :id="'pause-'.$listing->id" :title="($s === $S::Paused ? 'Reactivate ' : 'Pause ').$listing->name.'?'" :action="route('listings.status', $listing->id)" method="PATCH" :confirm="$s === $S::Paused ? 'Reactivate' : 'Pause listing'">
                        {{ $s === $S::Paused ? 'The listing will appear in search again.' : 'The listing will be hidden from search until you reactivate it.' }}
                        <input type="hidden" name="action" value="{{ $s === $S::Paused ? 'reactivate' : 'pause' }}">
                    </x-confirm-dialog>
                @endif
                @if ($s->ownerCanCancel())
                <x-confirm-dialog :id="'cancel-'.$listing->id" :title="'Cancel '.$listing->name.'?'" :action="route('listings.status', $listing->id)" method="PATCH" confirm="Cancel listing" variant="danger">
                    This removes the listing permanently. Anyone who requested it will be notified.
                    <input type="hidden" name="action" value="cancel">
                </x-confirm-dialog>
                @endif
            @endforeach
        </div>
    @endif
</x-layouts.app>
