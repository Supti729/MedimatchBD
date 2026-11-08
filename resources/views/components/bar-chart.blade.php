{{-- Dependency-free horizontal bar chart. $items = [label => value]. --}}
@props(['items', 'unit' => '', 'tone' => 'bg-brand-600'])
@php $max = max(1, max($items ?: [0])); @endphp
<ul {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @foreach ($items as $label => $value)
        <li class="grid grid-cols-[7rem_1fr_3rem] items-center gap-3 text-sm sm:grid-cols-[9rem_1fr_3rem]">
            <span class="truncate text-muted">{{ $label }}</span>
            <span class="h-3 overflow-hidden rounded-full bg-canvas" role="img" aria-label="{{ $label }}: {{ $value }}{{ $unit }}">
                <span class="block h-full rounded-full {{ $tone }}" style="width: {{ round($value / $max * 100) }}%"></span>
            </span>
            <span class="text-right font-semibold tabular-nums">{{ $value }}{{ $unit }}</span>
        </li>
    @endforeach
</ul>
