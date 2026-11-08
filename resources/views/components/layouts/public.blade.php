@props(['title' => null, 'description' => null])
@php
    $nav = [
        ['home', 'Home', 'home'],
        ['medicines.index', 'Find Medicine', 'medicines.*'],
        ['requests.create', 'Request Medicine', 'requests.create'],
        ['listings.create', 'Donate Medicine', 'listings.create'],
        ['how-it-works', 'How It Works', 'how-it-works'],
        ['safety', 'Safety', 'safety'],
    ];
@endphp
<x-layouts.base :title="$title" :description="$description ?? 'Medimatch BD connects people in Bangladesh who need eligible medicines with neighbours and organisations who can help.'">
    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4">
            <x-logo />
            <nav aria-label="Main" class="hidden items-center gap-1 text-sm font-medium lg:flex">
                @foreach ($nav as [$route, $label, $pattern])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}" @class(['rounded-lg px-3 py-2', 'bg-brand-50 text-brand-700 font-semibold' => $active, 'hover:text-brand-700' => ! $active]) @if ($active) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <x-button :href="auth()->user()->isStaff() ? route('admin.dashboard') : route('dashboard')" variant="ghost" size="sm" icon="dashboard">{{ auth()->user()->isStaff() ? 'Admin' : 'Dashboard' }}</x-button>
                    <form method="POST" action="{{ route('logout') }}">@csrf<x-button type="submit" variant="neutral" size="sm">Log out</x-button></form>
                @else
                    <x-button :href="route('login')" variant="ghost" size="sm">Log in</x-button>
                    <x-button :href="route('register')" size="sm">Register</x-button>
                @endauth
            </div>
            <button type="button" data-menu-toggle aria-controls="public-menu" aria-expanded="false" aria-label="Open menu" class="grid size-11 place-items-center rounded-lg border border-line lg:hidden">
                <x-icon name="menu" />
            </button>
        </div>
        <nav id="public-menu" aria-label="Mobile" class="hidden border-t border-line bg-white px-4 pt-2 pb-4 lg:hidden">
            @foreach ($nav as [$route, $label, $pattern])
                <a href="{{ route($route) }}" @class(['block rounded-lg px-3 py-3 font-medium', 'bg-brand-50 text-brand-700' => request()->routeIs($pattern)])>{{ $label }}</a>
            @endforeach
            <div class="mt-3 grid grid-cols-2 gap-2">
                @auth
                    <x-button :href="auth()->user()->isStaff() ? route('admin.dashboard') : route('dashboard')" variant="secondary">{{ auth()->user()->isStaff() ? 'Admin' : 'Dashboard' }}</x-button>
                    <form method="POST" action="{{ route('logout') }}" class="grid">@csrf<x-button type="submit" variant="neutral">Log out</x-button></form>
                @else
                    <x-button :href="route('login')" variant="secondary">Log in</x-button>
                    <x-button :href="route('register')">Register</x-button>
                @endauth
            </div>
        </nav>
    </header>

    <main id="main">
        @if (session()->hasAny(['success', 'error', 'warning', 'info']) || (isset($errors) && $errors->any()))
            <div class="mx-auto max-w-7xl px-4 pt-6"><x-flash /></div>
        @endif
        {{ $slot }}
    </main>

    <footer class="mt-16 bg-brand-900 text-brand-100">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-logo tone="light" />
                <p class="mt-3 text-sm">A university capstone prototype for privacy-focused medicine assistance in Bangladesh. Not a licensed pharmacy or medicine marketplace.</p>
            </div>
            <nav aria-label="Platform">
                <p class="font-semibold text-white">Platform</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('medicines.index') }}" class="hover:underline">Find Medicine</a></li>
                    <li><a href="{{ route('requests.create') }}" class="hover:underline">Request Medicine</a></li>
                    <li><a href="{{ route('listings.create') }}" class="hover:underline">Donate Medicine</a></li>
                    <li><a href="{{ route('how-it-works') }}" class="hover:underline">How It Works</a></li>
                </ul>
            </nav>
            <nav aria-label="Information">
                <p class="font-semibold text-white">Information</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="hover:underline">About</a></li>
                    <li><a href="{{ route('safety') }}" class="hover:underline">Safety Guidelines</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:underline">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:underline">Terms and Conditions</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:underline">Contact</a></li>
                </ul>
            </nav>
            <div>
                <p class="font-semibold text-white">Not medical advice</p>
                <p class="mt-3 text-sm">Medimatch BD never diagnoses or recommends treatment. Ask a doctor or pharmacist before taking any medicine. In an emergency call 999.</p>
            </div>
        </div>
        <div class="border-t border-white/15">
            <p class="mx-auto max-w-7xl px-4 py-4 text-xs text-brand-200">&copy; {{ date('Y') }} Medimatch BD · Software Engineering Capstone Project. Medicine sharing follows applicable Bangladesh regulations; this prototype is not an authorised service.</p>
        </div>
    </footer>
</x-layouts.base>
