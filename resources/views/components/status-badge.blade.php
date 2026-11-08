{{-- Accepts any App\Enums\HasBadge enum. Icon + text, never colour alone. --}}
@props(['status', 'size' => 'sm'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full font-semibold '.($size === 'xs' ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs').' '.$status->tone()]) }}><span aria-hidden="true">{{ $status->icon() }}</span>{{ $status->label() }}</span>
