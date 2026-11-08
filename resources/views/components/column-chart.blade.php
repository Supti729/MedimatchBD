{{-- Dependency-free column chart. $items = [label => value]. --}}
@props(['items'])
@php $max = max(1, max($items ?: [0])); @endphp
<div {{ $attributes->merge(['class' => 'flex h-48 items-end gap-2 sm:gap-3']) }} role="img" aria-label="{{ collect($items)->map(fn ($v, $k) => "$k: $v")->implode(', ') }}">
    @foreach ($items as $label => $value)
        <div class="flex h-full flex-1 flex-col items-center justify-end gap-1">
            <span class="text-xs font-semibold tabular-nums">{{ $value }}</span>
            <span class="w-full max-w-10 rounded-t-md bg-brand-600" style="height: {{ max(4, round($value / $max * 100)) }}%"></span>
            <span class="text-xs text-muted">{{ $label }}</span>
        </div>
    @endforeach
</div>
