{{-- Long-form text styling for policy and information pages. --}}
<div {{ $attributes->merge(['class' => 'mx-auto max-w-4xl px-4 py-12 text-ink [&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-brand-900 [&_h2:first-child]:mt-0 [&_li]:mt-1.5 [&_p]:mt-3 [&_p]:leading-relaxed [&_p]:text-muted [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:text-muted [&_a]:font-semibold [&_a]:text-brand-700 [&_a]:underline']) }}>
    {{ $slot }}
</div>
