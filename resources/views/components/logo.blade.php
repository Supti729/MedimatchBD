@props(['href' => null, 'tone' => 'dark'])
<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-extrabold tracking-tight '.($tone === 'light' ? 'text-white' : 'text-brand-900')]) }}>
    <span class="grid size-9 place-items-center rounded-lg bg-brand-600 text-white" aria-hidden="true">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    </span>
    <span class="text-lg">Medimatch <span class="{{ $tone === 'light' ? 'text-brand-200' : 'text-brand-600' }}">BD</span></span>
</a>
