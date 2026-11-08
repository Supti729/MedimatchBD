@props(['type' => 'info', 'title' => null, 'dismissible' => false])
@php
    $styles = [
        'info' => ['bg-brand-50 border-brand-200 text-brand-900', 'info'],
        'success' => ['bg-green-50 border-green-200 text-green-900', 'check'],
        'warning' => ['bg-warm-100 border-warm-500/40 text-warm-700', 'alert'],
        'error' => ['bg-red-50 border-red-200 text-red-900', 'alert'],
    ];
    [$tone, $icon] = $styles[$type] ?? $styles['info'];
@endphp
<div {{ $attributes->merge(['class' => "flex gap-3 rounded-xl border px-4 py-3 text-sm $tone"]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}" @if ($dismissible) data-dismissible @endif>
    <x-icon :name="$icon" class="mt-0.5 size-5" />
    <div class="min-w-0 flex-1">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" data-dismiss class="-m-1 h-fit rounded p-1 opacity-70 hover:opacity-100"><x-icon name="x" class="size-4" /><span class="sr-only">Dismiss</span></button>
    @endif
</div>
