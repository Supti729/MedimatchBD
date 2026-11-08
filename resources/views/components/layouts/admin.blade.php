@props(['title' => null])
@php
    // Each entry: route, label, icon, active pattern, required gate.
    $sections = [
        '' => [['admin.dashboard', 'Dashboard', 'dashboard', 'admin.dashboard', 'access-admin']],
        'People' => [
            ['admin.users.index', 'Users', 'users', 'admin.users.*', 'admin'],
            ['admin.partners.index', 'Partners', 'building', 'admin.partners.*', 'admin'],
        ],
        'Medicines' => [
            ['admin.medicines.index', 'Medicine Catalogue', 'pill', 'admin.medicines.*', 'admin'],
            ['admin.listings.index', 'Listings', 'list', 'admin.listings.*', 'admin'],
            ['admin.requests.index', 'Requests', 'inbox', 'admin.requests.*', 'admin'],
            ['admin.interactions.index', 'Interactions', 'history', 'admin.interactions.*', 'admin'],
        ],
        'Trust & safety' => [
            ['admin.verifications.index', 'Verification Center', 'shield', 'admin.verifications.*', 'review-verifications'],
            ['admin.reports.index', 'Reports', 'flag', 'admin.reports.*', 'admin'],
            ['admin.audit-logs.index', 'Audit Logs', 'file', 'admin.audit-logs.*', 'admin'],
        ],
        'Platform' => [
            ['admin.announcements.index', 'Announcements', 'megaphone', 'admin.announcements.*', 'admin'],
            ['admin.settings', 'Settings', 'settings', 'admin.settings', 'admin'],
        ],
    ];
@endphp
<x-layouts.base :title="($title ? $title.' · ' : '').'Admin'" body-class="bg-canvas">
    <div class="lg:flex">
        <aside class="bg-ink text-slate-200 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:overflow-y-auto">
            <div class="flex h-16 items-center justify-between px-4">
                <x-logo :href="route('admin.dashboard')" tone="light" />
                <button type="button" data-menu-toggle aria-controls="admin-menu" aria-expanded="false" aria-label="Open menu" class="grid size-11 place-items-center rounded-lg border border-white/20 lg:hidden"><x-icon name="menu" /></button>
            </div>
            <nav id="admin-menu" aria-label="Admin" class="hidden px-3 pb-6 lg:block">
                @foreach ($sections as $heading => $items)
                    @php $items = array_filter($items, fn ($i) => Gate::allows($i[4])); @endphp
                    @continue(! $items)
                    @if ($heading)<p class="mt-5 mb-2 px-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">{{ $heading }}</p>@endif
                    @foreach ($items as [$route, $label, $icon, $pattern, $gate])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium', 'bg-brand-600 text-white' => $active, 'hover:bg-white/10' => ! $active]) @if ($active) aria-current="page" @endif><x-icon :name="$icon" />{{ $label }}</a>
                    @endforeach
                @endforeach
                <a href="{{ route('home') }}" class="mt-6 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-400 hover:bg-white/10"><x-icon name="external" />View public site</a>
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-line bg-white px-4 lg:h-16 lg:px-8">
                <p class="text-sm text-muted">Signed in as <strong class="text-ink">{{ $currentUser->name }}</strong> · {{ $currentUser->role->label() }}</p>
                <form method="POST" action="{{ route('logout') }}">@csrf<x-button type="submit" variant="neutral" size="sm" icon="logout">Log out</x-button></form>
            </header>
            <main id="main" class="px-4 py-6 lg:px-8">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
