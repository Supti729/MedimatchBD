{{-- A public medicine listing. $layout: 'grid' (stacked card) or 'list' (horizontal row on wider screens). --}}
@props(['listing', 'layout' => 'grid', 'relevance' => null, 'method' => null])
@php $list = $layout === 'list'; @endphp
<article {{ $attributes->merge(['class' => 'card flex flex-col gap-4 p-5 transition hover:border-brand-200 hover:shadow-pop '.($list ? 'sm:flex-row sm:items-center' : '')]) }}>
    @php $photo = $listing->relationLoaded('images') ? $listing->images->first() : null; @endphp
    <div @class(['grid shrink-0 place-items-center overflow-hidden rounded-lg bg-brand-50 text-brand-600', 'size-14' => ! $list, 'size-14 sm:size-20' => $list])>
        @if ($photo)<img src="{{ $photo->url }}" alt="" class="size-full object-cover" loading="lazy">@else<x-icon name="pill" class="size-7" />@endif
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-lg leading-snug font-bold"><a href="{{ route('medicines.show', $listing->id) }}" class="hover:text-brand-700">{{ $listing->name }}</a></h3>
            <x-status-badge :status="$listing->status" />
        </div>
        <p class="text-sm text-muted">{{ $listing->generic }} · {{ $listing->strength }} · {{ $listing->form }}</p>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
            <dt class="text-muted">Quantity</dt><dd class="font-medium">{{ $listing->quantity_label }}</dd>
            <dt class="text-muted">Area</dt><dd class="flex items-center gap-1 font-medium"><x-icon name="map-pin" class="size-4 text-brand-600" />{{ $listing->owner?->privacySetting('show_upazila') === false ? '' : $listing->upazila.', ' }}{{ $listing->district }}</dd>
            <dt class="text-muted">Expiry</dt><dd class="font-medium">{{ $listing->expires_at->format('M Y') }}</dd>
        </dl>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <x-status-badge :status="$listing->type" size="xs" />
            @if ($listing->catalogue_verified)
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700"><x-icon name="shield" class="size-4" />Catalogue-matched</span>
            @endif
            @if ($relevance !== null)
                <span class="ml-auto text-xs font-semibold text-muted" title="How closely the listing fits the request (same medicine, strength and form; wording; distance). Not a measure of medical suitability.">{{ in_array($method, ['semantic', 'semantic_identity'], true) ? 'AI-ranked' : 'Match' }} relevance {{ $relevance }}%</span>
            @endif
            @if ($method === 'semantic_identity')
                <span class="w-full text-xs font-semibold text-warm-700">Medicine name suggested by AI — check it matches what you need.</span>
            @endif
        </div>
    </div>
    <x-button :href="route('medicines.show', $listing->id)" variant="secondary" :class="$list ? 'w-full sm:w-auto' : 'w-full'">View details<span class="sr-only"> for {{ $listing->name }}</span></x-button>
</article>
