<x-layouts.public title="How It Works">
    <x-page-hero title="How Medimatch BD works" eyebrow="Guide">One account lets you both offer and request medicine. Here is what happens at each step, for donors and for people looking for help.</x-page-hero>

    <div class="mx-auto max-w-5xl px-4 py-12">
        @php
            $flows = [
                'If you need medicine' => [
                    ['search', 'Search first', 'Search by brand or generic name and filter by your division, district and upazila.'],
                    ['inbox', 'Post a request', 'If nothing fits, create a request with the medicine, strength, form and quantity. Only your general area is shown.'],
                    ['bell', 'Get notified', 'When a relevant listing appears you receive a notification. Matching never decides medical suitability.'],
                    ['shield', 'Verification, when required', 'Some medicines need an authorised reviewer to check a prescription. The donor only sees "verified", never the document.'],
                    ['message', 'Chat and arrange', 'Agree a safe, public meeting place through private chat.'],
                    ['check', 'Confirm the hand-over', 'Check the seal and expiry yourself, then mark the interaction complete.'],
                ],
                'If you want to donate' => [
                    ['pill', 'Check eligibility', 'Only sealed, in-date, clearly identified medicine that is not on the restricted list can be listed.'],
                    ['plus', 'Create a listing', 'Choose the medicine from the verified catalogue, add quantity, expiry, condition and photos of the packaging.'],
                    ['clock', 'Review, when required', 'Some listings are reviewed before they go live. You are notified of the result.'],
                    ['message', 'Answer requests', 'Members who need the medicine can request it or start a private chat.'],
                    ['bookmark', 'Reserve', 'Reserve the medicine for one person while the hand-over is arranged.'],
                    ['check', 'Complete', 'After the hand-over the listing is marked complete and leaves search results.'],
                ],
            ];
        @endphp
        <div class="grid gap-10 lg:grid-cols-2">
            @foreach ($flows as $heading => $steps)
                <section>
                    <h2 class="text-2xl font-bold text-brand-900">{{ $heading }}</h2>
                    <ol class="mt-6 space-y-5 border-l-2 border-brand-100 pl-6">
                        @foreach ($steps as $i => [$icon, $title, $text])
                            <li class="relative">
                                <span class="absolute top-0 -left-[2.4rem] grid size-8 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $i + 1 }}</span>
                                <h3 class="flex items-center gap-2 font-bold"><x-icon :name="$icon" class="size-5 text-brand-600" />{{ $title }}</h3>
                                <p class="mt-1 text-sm text-muted">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>

        <section class="mt-14 grid gap-4 md:grid-cols-2">
            <div class="card p-6">
                <x-status-badge :status="\App\Enums\ListingType::Donation" />
                <h3 class="mt-3 font-bold">Donation</h3>
                <p class="mt-1 text-sm text-muted">Medicine is given free of charge. This is the main way the platform works, and it stays free for people who need help.</p>
            </div>
            <div class="card p-6">
                <x-status-badge :status="\App\Enums\ListingType::AffordableAccess" />
                <h3 class="mt-3 font-bold">Affordable Access</h3>
                <p class="mt-1 text-sm text-muted">Reduced-cost access offered only through programmes that are legally appropriate, such as an authorised partner organisation. Private resale of medicine is not allowed.</p>
            </div>
        </section>

        <div class="mt-12 flex flex-wrap gap-3">
            <x-button :href="route('medicines.index')" icon="search">Find Medicine</x-button>
            <x-button :href="route('listings.create')" variant="secondary" icon="heart">Donate Medicine</x-button>
            <x-button :href="route('requests.create')" variant="ghost" icon="inbox">Request Medicine</x-button>
        </div>
    </div>
</x-layouts.public>
