{{-- Member area layout. $currentUser, $headerNotifications, $unreadCount, $unreadMessages come from AppServiceProvider. --}}
@props(['title' => null])
@php
    $sections = [
        'Overview' => [
            ['dashboard', 'Dashboard', 'dashboard', 'dashboard'],
            ['medicines.index', 'Find Medicine', 'search', 'medicines.*'],
        ],
        'My activity' => [
            ['listings.index', 'My Listings', 'pill', 'listings.*'],
            ['requests.index', 'My Requests', 'inbox', 'requests.*'],
            ['saved', 'Saved Medicines', 'bookmark', 'saved'],
            ['messages.index', 'Messages', 'message', 'messages.*'],
            ['notifications.index', 'Notifications', 'bell', 'notifications.*'],
            ['history', 'Interaction History', 'history', 'history'],
        ],
        'Account' => [
            ['profile.show', 'Profile', 'user', 'profile.*'],
            ['verification.index', 'Verification Center', 'shield', 'verification.index'],
            ['settings.privacy', 'Privacy Settings', 'eye', 'settings.privacy'],
            ['settings.security', 'Security Settings', 'lock', 'settings.security'],
        ],
    ];
    $badges = ['messages.index' => $unreadMessages, 'notifications.index' => $unreadCount];
    $bottom = [
        ['dashboard', 'Home', 'home', 'dashboard'],
        ['medicines.index', 'Find', 'search', 'medicines.*'],
        ['listings.index', 'Listings', 'pill', 'listings.*'],
        ['messages.index', 'Messages', 'message', 'messages.*'],
    ];
@endphp
<x-layouts.base :title="$title" body-class="bg-canvas">
    <header class="sticky top-0 z-30 border-b border-line bg-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4">
            <x-logo :href="route('dashboard')" />
            <form action="{{ route('medicines.search') }}" method="GET" role="search" class="ml-6 hidden max-w-md flex-1 md:block">
                <label for="header-q" class="sr-only">Search medicines</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" />
                    <input id="header-q" type="search" name="q" maxlength="100" placeholder="Search medicines" class="field mt-0 bg-canvas py-2 pl-9">
                </div>
            </form>
            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <div class="hidden gap-2 xl:flex">
                    <x-button :href="route('listings.create')" variant="secondary" size="sm" icon="plus">Donate</x-button>
                    <x-button :href="route('requests.create')" size="sm" icon="plus">Request</x-button>
                </div>

                {{-- Notification dropdown --}}
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-trigger class="relative grid size-11 place-items-center rounded-lg text-ink hover:bg-canvas" aria-haspopup="true">
                        <x-icon name="bell" />
                        <span class="sr-only">Notifications, {{ $unreadCount }} unread</span>
                        @if ($unreadCount)
                            <span class="absolute top-1.5 right-1.5 grid min-w-5 place-items-center rounded-full bg-red-700 px-1 text-[11px] font-bold text-white" aria-hidden="true">{{ $unreadCount }}</span>
                        @endif
                    </button>
                    <div data-dropdown-panel hidden class="fixed inset-x-2 top-16 z-40 overflow-hidden rounded-xl border border-line bg-white shadow-pop sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-96">
                        <div class="flex items-center justify-between border-b border-line px-4 py-3">
                            <p class="font-bold">Notifications</p>
                            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-brand-700 hover:underline">Mark all as read</button></form>
                        </div>
                        <div class="max-h-96 divide-y divide-line overflow-y-auto">
                            @forelse ($headerNotifications as $notification)
                                <x-notification-item :notification="$notification" compact />
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-muted">You're all caught up.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-3 text-center text-sm font-semibold text-brand-700 hover:bg-canvas">View all notifications</a>
                    </div>
                </div>

                {{-- Account dropdown --}}
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-trigger class="flex items-center gap-2 rounded-lg p-1 hover:bg-canvas" aria-haspopup="true">
                        <x-avatar :user="$currentUser" size="sm" />
                        <span class="hidden text-sm font-semibold sm:block">{{ strtok($currentUser->name, ' ') }}</span>
                        <x-icon name="chevron-down" class="hidden size-4 text-muted sm:block" />
                        <span class="sr-only">Account menu</span>
                    </button>
                    <div data-dropdown-panel hidden class="absolute right-0 z-40 mt-2 w-60 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-pop">
                        <div class="border-b border-line px-4 py-3"><p class="font-semibold">{{ $currentUser->name }}</p><p class="truncate text-xs text-muted">{{ $currentUser->email ?? $currentUser->phone_display }}</p></div>
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-canvas"><x-icon name="user" class="size-4" />Profile</a>
                        <a href="{{ route('members.show', $currentUser->id) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-canvas"><x-icon name="eye" class="size-4" />View public profile</a>
                        <a href="{{ route('settings.security') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-canvas"><x-icon name="settings" class="size-4" />Settings</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line">@csrf<button class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-700 hover:bg-red-50"><x-icon name="logout" class="size-4" />Log out</button></form>
                    </div>
                </div>

                <button type="button" data-menu-toggle aria-controls="member-menu" aria-expanded="false" aria-label="Open menu" class="grid size-11 place-items-center rounded-lg border border-line lg:hidden"><x-icon name="menu" /></button>
            </div>
        </div>
    </header>

    <div class="mx-auto flex max-w-7xl gap-8 px-4 pt-6 pb-28 lg:pb-12">
        {{-- Sidebar (desktop) / slide-down menu (mobile) --}}
        <nav id="member-menu" aria-label="Account" class="hidden fixed inset-0 z-20 overflow-y-auto bg-white px-4 pt-28 pb-28 lg:sticky lg:inset-auto lg:top-24 lg:block lg:h-fit lg:w-60 lg:shrink-0 lg:bg-transparent lg:p-0">
            @foreach ($sections as $heading => $items)
                <p class="mt-5 mb-2 px-3 text-xs font-semibold tracking-wide text-muted uppercase first:mt-0">{{ $heading }}</p>
                @foreach ($items as [$route, $label, $icon, $pattern])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium', 'bg-brand-600 text-white' => $active, 'text-ink hover:bg-white' => ! $active]) @if ($active) aria-current="page" @endif>
                        <x-icon :name="$icon" class="size-5" /><span class="flex-1">{{ $label }}</span>
                        @if (! empty($badges[$route]))<span @class(['rounded-full px-2 text-xs font-bold', 'bg-white text-brand-700' => $active, 'bg-brand-100 text-brand-700' => ! $active])>{{ $badges[$route] }}</span>@endif
                    </a>
                @endforeach
            @endforeach
            <div class="mt-6 grid gap-2 xl:hidden">
                <x-button :href="route('listings.create')" variant="secondary" icon="plus">Donate Medicine</x-button>
                <x-button :href="route('requests.create')" icon="plus">Request Medicine</x-button>
            </div>
        </nav>

        <main id="main" class="min-w-0 flex-1">
            <x-flash />
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile bottom navigation --}}
    <nav aria-label="Quick" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
        <div class="mx-auto grid max-w-md grid-cols-5">
            @foreach (array_slice($bottom, 0, 2) as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" @class(['flex flex-col items-center gap-0.5 py-2 text-[11px] font-semibold', 'text-brand-700' => request()->routeIs($pattern), 'text-muted' => ! request()->routeIs($pattern)])><x-icon :name="$icon" />{{ $label }}</a>
            @endforeach
            <div class="relative flex justify-center" data-dropdown>
                <button type="button" data-dropdown-trigger class="-mt-5 grid size-14 place-items-center rounded-full bg-brand-600 text-white shadow-pop" aria-haspopup="true"><x-icon name="plus" class="size-6" /><span class="sr-only">Create new</span></button>
                <div data-dropdown-panel hidden class="absolute bottom-16 w-56 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-pop">
                    <a href="{{ route('listings.create') }}" class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:bg-canvas"><x-icon name="heart" class="size-4 text-brand-600" />Donate Medicine</a>
                    <a href="{{ route('requests.create') }}" class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:bg-canvas"><x-icon name="inbox" class="size-4 text-brand-600" />Request Medicine</a>
                </div>
            </div>
            @foreach (array_slice($bottom, 2) as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" @class(['relative flex flex-col items-center gap-0.5 py-2 text-[11px] font-semibold', 'text-brand-700' => request()->routeIs($pattern), 'text-muted' => ! request()->routeIs($pattern)])>
                    <x-icon :name="$icon" />{{ $label }}
                    @if ($route === 'messages.index' && $unreadMessages)<span class="absolute top-1 right-1/4 size-2 rounded-full bg-red-700"><span class="sr-only">{{ $unreadMessages }} unread</span></span>@endif
                </a>
            @endforeach
        </div>
    </nav>
</x-layouts.base>
