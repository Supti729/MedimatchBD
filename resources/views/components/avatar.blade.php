@props(['name', 'size' => 'md'])
@php
    $initials = collect(explode(' ', trim($name)))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $sizes = ['sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-lg', 'xl' => 'size-20 text-2xl'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-grid shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-700 '.($sizes[$size] ?? $sizes['md'])]) }} aria-hidden="true">{{ $initials }}</span>
