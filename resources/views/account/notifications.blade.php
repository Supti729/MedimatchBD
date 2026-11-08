<x-layouts.app title="Notifications">
    <x-page-header title="Notifications" :description="$unreadCount.' unread'">
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<x-button type="submit" variant="neutral" icon="check">Mark all as read</x-button></form>
        </x-slot:actions>
    </x-page-header>
    <nav aria-label="Filter notifications" class="mb-4 flex gap-2">
        @foreach (['all' => 'All', 'unread' => 'Unread'] as $key => $label)
            <a href="{{ route('notifications.index', $key === 'all' ? [] : ['filter' => $key]) }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-600 text-white' => $filter === $key, 'bg-white text-ink ring-1 ring-line hover:bg-canvas' => $filter !== $key]) @if ($filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @if ($notifications->isEmpty())
        <x-empty-state title="You're all caught up" icon="bell">New messages, matches and status updates will appear here.</x-empty-state>
    @else
        <div class="card divide-y divide-line overflow-hidden">
            @foreach ($notifications as $notification)
                <div class="flex items-center">
                    <x-notification-item :notification="$notification" class="flex-1" />
                    @unless ($notification->read)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="pr-3">@csrf @method('PATCH')<button class="rounded-lg px-2 py-1 text-xs font-semibold whitespace-nowrap text-brand-700 hover:bg-brand-50">Mark read</button></form>
                    @endunless
                </div>
            @endforeach
        </div>
    @endif
    <p class="mt-4 text-xs text-muted">Notifications are shown in the platform. Email and SMS delivery are only used when a provider is configured.</p>
</x-layouts.app>
