@props(['href' => null, 'variant' => 'primary', 'size' => 'md', 'icon' => null, 'type' => 'button'])
@php
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 shadow-sm',
        'secondary' => 'border-2 border-brand-600 bg-white text-brand-700 hover:bg-brand-50',
        'neutral' => 'border border-line bg-white text-ink hover:bg-canvas',
        'ghost' => 'text-brand-700 hover:bg-brand-50',
        'danger' => 'bg-red-700 text-white hover:bg-red-800 shadow-sm',
        'danger-outline' => 'border border-red-300 bg-white text-red-700 hover:bg-red-50',
        'light' => 'bg-white text-brand-900 hover:bg-brand-50',
    ];
    $sizes = [
        'sm' => 'min-h-9 px-3 py-1.5 text-sm gap-1.5',
        'md' => 'min-h-11 px-4 py-2.5 text-sm gap-2',
        'lg' => 'min-h-12 px-6 py-3 text-base gap-2',
    ];
    $classes = 'inline-flex items-center justify-center rounded-lg font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<x-icon :name="$icon" class="size-4" />@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<x-icon :name="$icon" class="size-4" />@endif{{ $slot }}</button>
@endif
