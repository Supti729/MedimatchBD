<x-layouts.public>
    {{-- Hero + search --}}
    <section class="border-b border-line bg-canvas">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 lg:grid-cols-[1.15fr_1fr] lg:py-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-semibold text-brand-700 ring-1 ring-line"><x-icon name="map-pin" class="size-4" />For communities across Bangladesh</p>
                <h1 class="mt-5 text-4xl leading-[1.1] font-extrabold tracking-tight text-brand-900 sm:text-5xl lg:text-6xl">Find the medicine you need. Share what someone else needs.</h1>
                <p class="mt-5 max-w-xl text-lg text-muted">Medimatch BD helps people find eligible, unused medicine near them and pass on what they no longer need, through private chat, careful eligibility rules and reviewer checks where they are required.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-button :href="route('medicines.index')" size="lg" icon="search">Find Medicine</x-button>
                    <x-button :href="route('listings.create')" size="lg" variant="secondary" icon="heart">Donate Medicine</x-button>
                    <x-button :href="route('requests.create')" size="lg" variant="ghost" icon="inbox">Request Medicine</x-button>
                </div>
            </div>

            <div class="card p-5 sm:p-6">
                <h2 class="text-lg font-bold">What medicine are you looking for?</h2>
                <p class="text-sm text-muted">Search by brand, generic name or strength.</p>
                <form action="{{ route('medicines.search') }}" method="GET" role="search" class="mt-5 space-y-4">
                    <div>
                        <label for="hero-q" class="label">Medicine name</label>
                        <input id="hero-q" name="q" type="search" maxlength="100" placeholder="e.g. Napa 500, Paracetamol" class="field py-3">
                    </div>
                    <x-form.location :required="false" :with-upazila="false" prefix="hero" />
                    <fieldset>
                        <legend class="label">Listing type</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (\App\Enums\ListingType::cases() as $type)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="type[]" value="{{ $type->value }}" class="peer sr-only">
                                    <span class="inline-flex min-h-10 items-center rounded-full border border-line px-4 text-sm font-medium peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-warm-500">{{ $type->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <x-button type="submit" size="lg" icon="search" class="w-full">Search medicines</x-button>
                </form>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-7xl px-4 py-16">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-extrabold tracking-tight text-brand-900">How it works</h2>
            <p class="mt-2 text-muted">Six steps from sign-up to a completed hand-over. One account lets you both give and receive.</p>
        </div>
        @php
            $steps = [
                ['user', 'Register', 'Sign up with your email or Bangladeshi phone number and choose your area.'],
                ['search', 'Search or list', 'Look for a medicine, post a request, or list one you can give.'],
                ['sparkles', 'Get matched', 'Requests are compared with eligible listings near you.'],
                ['shield', 'Verify when required', 'Some medicines need a check by an authorised reviewer first.'],
                ['message', 'Connect privately', 'Chat inside the platform. Phone numbers stay hidden.'],
                ['check', 'Complete', 'Meet safely, hand over, and mark the exchange complete.'],
            ];
        @endphp
        <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($steps as $i => [$icon, $title, $text])
                <li class="card p-6">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                        <x-icon :name="$icon" class="size-6 text-brand-600" />
                    </div>
                    <h3 class="mt-4 font-bold">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
        <a href="{{ route('how-it-works') }}" class="link mt-6 inline-block">Read the full guide</a>
    </section>

    {{-- Donate + request --}}
    <section class="border-y border-line bg-canvas">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-16 md:grid-cols-2">
            <div class="rounded-2xl bg-brand-900 p-8 text-white">
                <x-icon name="heart" class="size-8 text-brand-200" />
                <h2 class="mt-4 text-2xl font-bold">Have unused, eligible medicine?</h2>
                <p class="mt-3 text-brand-100">Sealed, in-date medicine in good condition can help someone nearby. Listings are checked against the medicine catalogue, and some are reviewed before they go live.</p>
                <ul class="mt-5 space-y-2 text-sm text-brand-100">
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-brand-200" />Sealed and within expiry</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-brand-200" />Clearly identified brand, strength and form</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-brand-200" />Not on the restricted list</li>
                </ul>
                <x-button :href="route('listings.create')" variant="light" size="lg" class="mt-7">Donate Medicine</x-button>
            </div>
            <div class="card rounded-2xl p-8">
                <x-icon name="inbox" class="size-8 text-brand-600" />
                <h2 class="mt-4 text-2xl font-bold">Can't find the medicine you need?</h2>
                <p class="mt-3 text-muted">Post a request with the medicine, strength and your area. When a relevant listing appears, you'll get a notification and can start a private chat.</p>
                <ul class="mt-5 space-y-2 text-sm text-muted">
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-brand-600" />Only your general area is shown</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-brand-600" />Prescriptions go to reviewers, not donors</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-brand-600" />Free for people who need help</li>
                </ul>
                <x-button :href="route('requests.create')" size="lg" class="mt-7">Create a Request</x-button>
            </div>
        </div>
    </section>

    {{-- Trust & safety --}}
    <section class="mx-auto max-w-7xl px-4 py-16">
        <h2 class="text-3xl font-extrabold tracking-tight text-brand-900">Built around privacy and safety</h2>
        <p class="mt-2 max-w-2xl text-muted">The rules the platform is designed around. Real-world operation must follow applicable Bangladesh regulations.</p>
        @php
            $rules = [
                ['lock', 'Private profiles', 'Others see only your name, photo, joined date and a trust indicator. Never your phone, email or address.'],
                ['message', 'In-platform chat', 'Talk to the other person without sharing contact details.'],
                ['shield', 'Verification workflow', 'Prescriptions go only to an authorised reviewer. The donor sees only the result.'],
                ['flag', 'Reporting', 'Report a listing, member or message. Moderators review and record every decision.'],
                ['pill', 'Controlled listings', 'Expired, damaged, unidentified or restricted medicine cannot be listed.'],
            ];
        @endphp
        <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($rules as [$icon, $title, $text])
                <li class="card p-6"><x-icon :name="$icon" class="size-7 text-brand-600" /><h3 class="mt-3 font-bold">{{ $title }}</h3><p class="mt-1 text-sm text-muted">{{ $text }}</p></li>
            @endforeach
            <li class="rounded-xl bg-warm-100 p-6 text-warm-700"><x-icon name="alert" class="size-7" /><h3 class="mt-3 font-bold">Not medical advice</h3><p class="mt-1 text-sm">Medimatch BD does not diagnose, prescribe or recommend treatment. Ask a doctor or pharmacist before taking any medicine.</p></li>
        </ul>
        <a href="{{ route('safety') }}" class="link mt-6 inline-block">Read the safety guidelines</a>
    </section>

    {{-- Location + AI matching --}}
    <section class="border-y border-line bg-canvas">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-extrabold text-brand-900">Search by your area</h2>
                <p class="mt-2 text-muted">Narrow results from division to district to upazila, or search all of Bangladesh. Only general areas are ever shown.</p>
                <div class="mt-6 flex flex-col gap-3 text-sm sm:flex-row sm:items-center">
                    <span class="card px-4 py-3 font-semibold">Division<span class="block font-normal text-muted">Dhaka</span></span>
                    <x-icon name="arrow-right" class="hidden text-muted sm:block" />
                    <span class="card px-4 py-3 font-semibold">District<span class="block font-normal text-muted">Dhaka</span></span>
                    <x-icon name="arrow-right" class="hidden text-muted sm:block" />
                    <span class="rounded-xl border border-brand-600 bg-brand-50 px-4 py-3 font-semibold">Upazila / Thana<span class="block font-normal text-muted">Mirpur</span></span>
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-brand-900">Smarter matching <span class="align-middle text-xs font-semibold text-muted">(coming in Part 3)</span></h2>
                <p class="mt-2 text-muted">Different wording, same medicine. AI-assisted matching will compare a request with eligible listings and rank them by how closely the descriptions fit. Strict checks on medicine identity, strength and form always apply first.</p>
                <div class="mt-6 flex flex-col gap-3 text-sm sm:flex-row sm:items-center">
                    <span class="card px-4 py-3">"I need paracetamol 500 mg tablets"</span>
                    <x-icon name="arrow-right" class="hidden text-muted sm:block" />
                    <span class="rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white">Matching</span>
                    <x-icon name="arrow-right" class="hidden text-muted sm:block" />
                    <span class="card px-4 py-3">Napa 500 · Paracetamol 500 mg Tablet</span>
                </div>
                <p class="mt-3 text-xs text-muted">Match relevance describes how closely the text fits. It never decides medical suitability or equivalence.</p>
            </div>
        </div>
    </section>

    {{-- Recent listings --}}
    <section class="mx-auto max-w-7xl px-4 py-16">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-brand-900">Recently listed</h2>
                <p class="mt-1 text-sm text-muted">The newest medicines shared by members.</p>
            </div>
            <a href="{{ route('medicines.index') }}" class="link shrink-0 text-sm">Browse all</a>
        </div>
        @if ($recent->isEmpty())
            <x-empty-state title="No medicines listed yet" icon="pill" class="mt-8">Be the first to share an unused, eligible medicine.<x-slot:actions><x-button :href="route('listings.create')" icon="heart">Donate Medicine</x-button></x-slot:actions></x-empty-state>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recent as $listing)
                    <x-medicine-card :listing="$listing" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Community impact (no invented numbers) --}}
    <section class="mx-auto max-w-7xl px-4">
        <div class="rounded-2xl border border-line bg-white p-8 sm:p-10">
            <h2 class="text-2xl font-extrabold text-brand-900">Community impact</h2>
            <p class="mt-2 max-w-2xl text-muted">These figures will be published once the platform has run long enough for them to be meaningful. We don't show made-up numbers.</p>
            <dl class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['Medicines shared', 'Requests fulfilled', 'Districts reached', 'Active members'] as $metric)
                    <div class="rounded-xl bg-canvas p-5">
                        <dt class="text-sm text-muted">{{ $metric }}</dt>
                        <dd class="mt-1 text-3xl font-extrabold text-ink" aria-label="{{ $metric }}: not available yet">—</dd>
                        <dd class="text-xs text-muted">Available after launch</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
</x-layouts.public>
