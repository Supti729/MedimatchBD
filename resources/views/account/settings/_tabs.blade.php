<nav aria-label="Settings" class="mb-6 flex gap-1 border-b border-line">
    @foreach (['settings.privacy' => 'Privacy', 'settings.security' => 'Security'] as $route => $label)
        <a href="{{ route($route) }}" @class(['-mb-px border-b-2 px-4 py-3 text-sm font-semibold', 'border-brand-600 text-brand-700' => request()->routeIs($route), 'border-transparent text-muted hover:text-ink' => ! request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
