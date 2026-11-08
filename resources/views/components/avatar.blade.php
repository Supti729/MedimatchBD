{{-- Initials avatar, or the member's photo when they chose to show it. Pass either :user or name. --}}
@props(['name' => null, 'user' => null, 'size' => 'md'])
@php
    $name ??= $user?->name ?? '?';
    $photo = $user && $user->avatar_path && $user->privacySetting('show_photo') ? $user->avatar_url : null;
    $initials = collect(explode(' ', trim($name)))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $sizes = ['sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-lg', 'xl' => 'size-20 text-2xl'];
@endphp
@if ($photo)
    <img src="{{ $photo }}" alt="" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover '.($sizes[$size] ?? $sizes['md'])]) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-grid shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-700 '.($sizes[$size] ?? $sizes['md'])]) }} aria-hidden="true">{{ $initials }}</span>
@endif
