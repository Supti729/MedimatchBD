@props(['label', 'value', 'icon' => 'chart', 'hint' => null, 'href' => null, 'tone' => 'brand'])
@php $tones = ['brand' => 'bg-brand-50 text-brand-700', 'warm' => 'bg-warm-100 text-warm-700', 'blue' => 'bg-blue-50 text-blue-700', 'slate' => 'bg-slate-100 text-slate-700', 'red' => 'bg-red-50 text-red-700']; @endphp
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card flex items-start gap-4 p-5'.($href ? ' transition hover:border-brand-600' : '')]) }}>
    <span class="grid size-11 shrink-0 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}"><x-icon :name="$icon" /></span>
    <span class="min-w-0">
        <span class="block text-sm text-muted">{{ $label }}</span>
        <span class="block text-2xl font-extrabold text-ink">{{ $value }}</span>
        @if ($hint)<span class="block text-xs text-muted">{{ $hint }}</span>@endif
    </span>
</{{ $tag }}>
