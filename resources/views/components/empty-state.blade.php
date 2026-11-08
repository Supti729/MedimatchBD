@props(['title', 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-line bg-canvas px-6 py-10 text-center']) }}>
    <span class="mx-auto grid size-12 place-items-center rounded-full bg-white text-brand-600 shadow-card"><x-icon :name="$icon" class="size-6" /></span>
    <p class="mt-4 font-bold text-ink">{{ $title }}</p>
    @if (trim($slot))<div class="mx-auto mt-1 max-w-md text-sm text-muted">{{ $slot }}</div>@endif
    @isset($actions)<div class="mt-5 flex flex-wrap justify-center gap-2">{{ $actions }}</div>@endisset
</div>
