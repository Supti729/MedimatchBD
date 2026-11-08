{{-- Key/value list. $items = [label => value(html-safe string or HtmlString)] --}}
@props(['items'])
<dl {{ $attributes->merge(['class' => 'divide-y divide-line rounded-xl border border-line text-sm']) }}>
    @foreach ($items as $label => $value)
        <div class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:justify-between sm:gap-4"><dt class="text-muted">{{ $label }}</dt><dd class="font-medium sm:text-right">{{ $value }}</dd></div>
    @endforeach
</dl>
