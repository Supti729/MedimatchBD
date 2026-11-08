@props(['title', 'heading', 'subheading' => null, 'variant' => 'member'])
<x-layouts.base :title="$title" body-class="bg-canvas">
    <main id="main" class="grid min-h-[calc(100vh-2rem)] lg:grid-cols-2">
        <section @class(['relative hidden flex-col justify-between overflow-hidden p-12 text-white lg:flex', 'bg-brand-900' => $variant === 'member', 'bg-ink' => $variant === 'admin'])>
            <x-logo tone="light" />
            <div class="max-w-md">
                @if ($variant === 'admin')
                    <p class="text-3xl font-extrabold leading-tight">Administration console</p>
                    <p class="mt-4 text-brand-100">Moderation, verification review and platform management. Every administrative action is recorded in the audit log.</p>
                @else
                    <p class="text-3xl font-extrabold leading-tight">Find the medicine you need. Share what someone else needs.</p>
                    <ul class="mt-8 space-y-4 text-brand-100">
                        <li class="flex gap-3"><x-icon name="lock" class="mt-0.5 text-brand-200" />Your phone, email and address are never shown to other members.</li>
                        <li class="flex gap-3"><x-icon name="message" class="mt-0.5 text-brand-200" />Talk through private in-platform chat.</li>
                        <li class="flex gap-3"><x-icon name="shield" class="mt-0.5 text-brand-200" />Prescriptions go only to authorised reviewers, never to donors.</li>
                    </ul>
                @endif
            </div>
            <p class="text-xs text-brand-200">University capstone prototype · not a licensed pharmacy.</p>
            <div class="pointer-events-none absolute -right-24 -bottom-24 size-96 rounded-full border-[48px] border-white/5" aria-hidden="true"></div>
        </section>
        <section class="flex flex-col items-center justify-center px-4 py-10 sm:px-8">
            <div class="mb-8 lg:hidden"><x-logo /></div>
            <div class="card w-full max-w-md p-6 sm:p-8">
                <h1 class="text-2xl font-extrabold tracking-tight text-brand-900">{{ $heading }}</h1>
                @if ($subheading)<p class="mt-1 text-muted">{{ $subheading }}</p>@endif
                <div class="mt-6"><x-flash />{{ $slot }}</div>
            </div>
            @isset($footer)<div class="mt-6 text-center text-sm text-muted">{{ $footer }}</div>@endisset
        </section>
    </main>
</x-layouts.base>
