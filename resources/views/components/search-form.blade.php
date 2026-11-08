{{-- Main medicine search. Submits GET to the search results page. --}}
@props(['q' => '', 'division' => '', 'large' => false])
<form action="{{ route('medicines.search') }}" method="GET" role="search" {{ $attributes->merge(['class' => 'grid gap-3 md:grid-cols-[1fr_14rem_auto]']) }}>
    <div class="relative">
        <label for="search-q" class="sr-only">Medicine, generic or brand name</label>
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-muted" />
        <input id="search-q" name="q" type="search" value="{{ $q }}" maxlength="100" placeholder="Medicine, generic or brand name, e.g. Napa 500" class="field mt-0 pl-11 {{ $large ? 'py-3.5 text-base' : '' }}" autocomplete="off">
    </div>
    <div>
        <label for="search-division" class="sr-only">Division</label>
        <select id="search-division" name="division" class="field mt-0 {{ $large ? 'py-3.5' : '' }}">
            <option value="">Anywhere in Bangladesh</option>
            @foreach (array_keys(\App\Models\Location::tree()) as $name)
                <option value="{{ $name }}" @selected($division === $name)>{{ $name }} Division</option>
            @endforeach
        </select>
    </div>
    <x-button type="submit" icon="search" :size="$large ? 'lg' : 'md'">Search</x-button>
</form>
