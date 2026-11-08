{{-- Browse (medicines.index) and Search results (medicines.search) share this view. --}}
@php
    $activeCount = collect($filters)->except(['q', 'sort', 'availability'])->filter()->count() + ($filters['availability'] === 'all' ? 1 : 0);
    $query = request()->except('page');
@endphp
<x-layouts.public :title="$isSearch ? ($filters['q'] ? 'Results for “'.$filters['q'].'”' : 'Search results') : 'Find Medicine'">
    <section class="border-b border-line bg-canvas">
        <div class="mx-auto max-w-7xl px-4 py-8">
            @if ($isSearch)
                <nav aria-label="Breadcrumb" class="mb-2 text-sm text-muted"><a href="{{ route('medicines.index') }}" class="hover:underline">Find Medicine</a> / Search results</nav>
                <h1 class="text-2xl font-extrabold tracking-tight text-brand-900 sm:text-4xl">{{ $filters['q'] ? 'Results for “'.$filters['q'].'”' : 'All matching medicines' }}</h1>
            @else
                <h1 class="text-2xl font-extrabold tracking-tight text-brand-900 sm:text-4xl">Find Medicine</h1>
                <p class="mt-1 text-muted">Browse eligible medicines shared by people across Bangladesh.</p>
            @endif
            <x-search-form :q="$filters['q']" :division="$filters['division']" class="mt-5" large />
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-[17rem_1fr]">
        {{-- Filters: sidebar on desktop, full-screen drawer on mobile --}}
        <aside id="filters" data-drawer aria-label="Filters" class="fixed inset-0 z-40 hidden overflow-y-auto bg-white p-5 data-open:block lg:static lg:z-auto lg:block lg:overflow-visible lg:bg-transparent lg:p-0">
            <form action="{{ route($route) }}" method="GET" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Filters</h2>
                    <button type="button" data-drawer-close class="rounded-lg border border-line px-3 py-2 text-sm font-semibold lg:hidden">Close</button>
                </div>
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                @if ($layout === 'list')<input type="hidden" name="view" value="list">@endif

                <x-form.location :division="$filters['division']" :district="$filters['district']" :upazila="$filters['upazila']" prefix="filter" compact />

                <fieldset>
                    <legend class="label">Listing type</legend>
                    <div class="mt-2 space-y-2">
                        @foreach (\App\Enums\ListingType::cases() as $type)
                            <x-form.checkbox name="type[]" :value="$type->value" :label="$type->label()" :checked="in_array($type->value, $filters['type'], true)" />
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="label">Availability</legend>
                    <div class="mt-2 space-y-2 text-sm">
                        <label class="flex items-center gap-3"><input type="radio" name="availability" value="available" @checked($filters['availability'] === 'available') class="size-5 accent-brand-600"> Available now</label>
                        <label class="flex items-center gap-3"><input type="radio" name="availability" value="all" @checked($filters['availability'] === 'all') class="size-5 accent-brand-600"> Include requested & reserved</label>
                    </div>
                </fieldset>

                <x-form.select name="form" label="Dosage form" placeholder="Any form" :options="\App\Http\Requests\MedicineRequestRequest::FORMS" :value="$filters['form']" />
                <x-form.input name="strength" label="Strength" :value="$filters['strength']" placeholder="e.g. 500 mg" maxlength="30" />

                <div class="flex gap-2">
                    <x-button type="submit" class="flex-1">Apply filters</x-button>
                    <x-button :href="route($route, array_filter(['q' => $filters['q']]))" variant="neutral">Clear</x-button>
                </div>
            </form>
        </aside>

        <section aria-labelledby="results-heading" class="min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="results-heading" class="font-semibold" aria-live="polite">{{ $results->total() }} {{ Str::plural('medicine', $results->total()) }} found</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-drawer-open="filters" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-brand-600 px-4 text-sm font-semibold text-brand-700 lg:hidden">
                        <x-icon name="filter" class="size-4" />Filters @if ($activeCount)<span class="rounded-full bg-brand-600 px-2 text-xs text-white">{{ $activeCount }}</span>@endif
                    </button>
                    <form action="{{ route($route) }}" method="GET">
                        @foreach (\Illuminate\Support\Arr::except($query, ['sort']) as $key => $value)
                            @foreach ((array) $value as $v)<input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">@endforeach
                        @endforeach
                        <label for="sort" class="sr-only">Sort by</label>
                        <select id="sort" name="sort" data-autosubmit class="field mt-0 min-h-11 py-2 text-sm">
                            @foreach (\App\Support\ListingSearch::SORTS as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>Sort: {{ $label }}</option>
                            @endforeach
                        </select>
                        <noscript><x-button type="submit" size="sm" variant="neutral">Sort</x-button></noscript>
                    </form>
                    <div class="hidden overflow-hidden rounded-lg border border-line text-sm sm:flex" role="group" aria-label="Layout">
                        @foreach (['grid' => 'Grid', 'list' => 'List'] as $value => $label)
                            <a href="{{ route($route, array_merge($query, ['view' => $value])) }}" @class(['inline-flex min-h-11 items-center gap-1.5 px-3', 'bg-brand-50 font-semibold text-brand-700' => $layout === $value, 'bg-white hover:bg-canvas' => $layout !== $value]) @if ($layout === $value) aria-current="true" @endif>
                                <x-icon :name="$value === 'grid' ? 'grid' : 'rows'" class="size-4" />{{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($suggestions->isNotEmpty())
                <x-alert type="info" title="No exact matches — showing AI suggestions" class="mt-4">
                    Nothing matched “{{ $filters['q'] }}” word for word, so our matching model suggested the closest medicines in the catalogue:
                    <strong>{{ $suggestions->map(fn ($m) => $m->brand_name.' ('.$m->generic_name.' '.$m->strength.' '.$m->dosage_form.')')->implode(', ') }}</strong>.
                    Please check the name, strength and form before requesting. This is text similarity, not medical advice.
                </x-alert>
            @endif

            @if ($results->isEmpty())
                <x-empty-state title="No eligible medicine matches your search" icon="search" class="mt-6">
                    Try a generic name, a different strength, or a wider area. You can also post a request and we'll notify you when a match appears.
                    <x-slot:actions>
                        <x-button :href="route('requests.create')" icon="plus">Create a Request</x-button>
                        <x-button :href="route('medicines.index')" variant="neutral">Clear search</x-button>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <div @class(['mt-5 grid gap-4', 'sm:grid-cols-2 xl:grid-cols-3' => $layout === 'grid'])>
                    @foreach ($results as $listing)
                        <x-medicine-card :listing="$listing" :layout="$layout" />
                    @endforeach
                </div>
                {{ $results->links() }}
            @endif
            <p class="mt-6 text-xs text-muted">Blocked, draft, unreviewed and expired listings never appear in search.</p>
        </section>
    </div>
</x-layouts.public>
