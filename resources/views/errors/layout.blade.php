<x-layouts.public :title="$title">
    <div class="mx-auto max-w-xl px-4 py-20 text-center">
        <p class="text-6xl font-extrabold text-brand-200">{{ $code }}</p>
        <h1 class="mt-4 text-2xl font-extrabold text-brand-900">{{ $title }}</h1>
        <p class="mt-2 text-muted">{{ $message }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-button :href="route('home')" icon="home">Go home</x-button>
            <x-button :href="route('medicines.index')" variant="neutral" icon="search">Find Medicine</x-button>
        </div>
    </div>
</x-layouts.public>
