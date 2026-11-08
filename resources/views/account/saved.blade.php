<x-layouts.app title="Saved Medicines">
    <x-page-header title="Saved medicines" description="Listings you bookmarked. You'll be notified if a saved listing changes status." />
    @if ($listings->isEmpty())
        <x-empty-state title="Nothing saved yet" icon="bookmark">Tap “Save” on any listing to keep it here.<x-slot:actions><x-button :href="route('medicines.index')" icon="search">Find Medicine</x-button></x-slot:actions></x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($listings as $listing)
                <div class="relative">
                    <x-medicine-card :listing="$listing" />
                    <form method="POST" action="{{ route('saved.destroy', $listing->id) }}" class="absolute top-4 right-4">
                        @csrf @method('DELETE')
                        <button class="grid size-9 place-items-center rounded-full bg-white text-red-700 shadow-card ring-1 ring-line hover:bg-red-50" title="Remove from saved"><x-icon name="trash" class="size-4" /><span class="sr-only">Remove {{ $listing->name }} from saved</span></button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
