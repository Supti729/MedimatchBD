@props(['title', 'eyebrow' => null])
<section class="border-b border-line bg-canvas">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:py-16">
        @if ($eyebrow)<p class="text-sm font-semibold tracking-wide text-brand-700 uppercase">{{ $eyebrow }}</p>@endif
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand-900 sm:text-5xl">{{ $title }}</h1>
        @if (trim($slot))<div class="mt-4 max-w-2xl text-lg text-muted">{{ $slot }}</div>@endif
    </div>
</section>
