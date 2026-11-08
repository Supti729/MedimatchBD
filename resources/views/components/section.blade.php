@props(['title' => null, 'description' => null, 'padded' => true])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div><h2 class="font-bold text-ink">{{ $title }}</h2>@if ($description)<p class="text-sm text-muted">{{ $description }}</p>@endif</div>
            @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['p-5' => $padded])>{{ $slot }}</div>
</section>
