{{-- A database notification. Clicking marks it read and opens the related page. --}}
@props(['notification', 'compact' => false])
@php
    $icons = ['message' => 'message', 'match' => 'sparkles', 'verification' => 'shield', 'listing' => 'pill', 'reservation' => 'bookmark', 'completion' => 'check', 'report' => 'flag', 'announcement' => 'megaphone', 'request' => 'inbox'];
    $data = $notification->data;
    $unread = $notification->read_at === null;
@endphp
<a href="{{ route('notifications.open', $notification->id) }}" {{ $attributes->merge(['class' => 'flex gap-3 px-4 py-3 hover:bg-canvas '.($unread ? 'bg-brand-50/60' : '')]) }}>
    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white text-brand-700 ring-1 ring-line"><x-icon :name="$icons[$data['type'] ?? ''] ?? 'bell'" class="size-4" /></span>
    <span class="min-w-0 flex-1">
        <span class="flex items-start justify-between gap-2">
            <span class="text-sm font-semibold text-ink">{{ $data['title'] ?? 'Notification' }}</span>
            @if ($unread)<span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600"><span class="sr-only">Unread</span></span>@endif
        </span>
        <span @class(['block text-sm text-muted', 'line-clamp-1' => $compact])>{{ $data['body'] ?? '' }}</span>
        <span class="block text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</span>
    </span>
</a>
