@props(['title', 'description' => null, 'back' => null, 'backLabel' => 'Back'])
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" />{{ $backLabel }}</a>
        @endif
        <h1 class="text-2xl font-extrabold tracking-tight text-brand-900 sm:text-3xl">{{ $title }}</h1>
        @if ($description)<p class="mt-1 max-w-2xl text-muted">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
</div>
