<x-layouts.public :title="$listing->name.' · '.$listing->generic">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <nav aria-label="Breadcrumb" class="text-sm text-muted"><a href="{{ route('medicines.index') }}" class="hover:underline">Find Medicine</a> <span aria-hidden="true">/</span> {{ $listing->name }}</nav>

        <div class="mt-5 grid gap-8 lg:grid-cols-2">
            {{-- Photo gallery (placeholder until uploads exist) --}}
            <div>
                <div class="grid aspect-[4/3] place-items-center rounded-2xl border border-line bg-brand-50 text-center text-muted">
                    <div><x-icon name="camera" class="mx-auto size-12 text-brand-600" /><p class="mt-2 text-sm">Packaging photos appear here</p></div>
                </div>
                <div class="mt-3 grid grid-cols-4 gap-2" aria-hidden="true">
                    <div class="aspect-square rounded-lg border-2 border-brand-600 bg-brand-100"></div>
                    @for ($i = 0; $i < 3; $i++)<div class="aspect-square rounded-lg border border-line bg-brand-50"></div>@endfor
                </div>
            </div>

            <div>
                <div class="flex flex-wrap gap-2">
                    <x-status-badge :status="$listing->type" />
                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-800"><x-icon name="shield" class="size-4" />Catalogue-matched</span>
                    @if ($listing->eligibility === \App\Enums\MedicineEligibility::VerificationRequired)<x-status-badge :status="$listing->eligibility" />@endif
                </div>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-900 sm:text-4xl">{{ $listing->name }}</h1>
                <p class="text-lg text-muted">{{ $listing->generic }}</p>

                <x-info-list class="mt-5" :items="[
                    'Strength' => $listing->strength,
                    'Dosage form' => $listing->form,
                    'Available quantity' => $listing->quantity.' ('.$listing->quantity_note.')',
                    'Expiry' => $listing->expires_at->format('F Y'),
                    'Packaging' => $listing->packaging,
                    'General area' => $listing->upazila.', '.$listing->district,
                    'Listed' => $listing->created_at->diffForHumans(),
                ]" />
                <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-line px-4 py-3 text-sm"><span class="text-muted">Listing status</span><x-status-badge :status="$listing->status" /></div>
                <p class="mt-2 text-xs text-muted">The exact address is never shown. Batch numbers are visible to reviewers only.</p>

                @if ($listing->status === \App\Enums\ListingStatus::Available)
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <x-button :href="route('login')" size="lg" icon="inbox">Request This Medicine</x-button>
                        <x-button :href="route('login')" size="lg" variant="secondary" icon="message">Start Chat</x-button>
                    </div>
                    <p class="mt-2 text-sm text-muted">You need to <a href="{{ route('login') }}" class="link">log in</a> or <a href="{{ route('register') }}" class="link">register</a> to request or chat.</p>
                @else
                    <x-alert type="warning" class="mt-6">This medicine is currently <strong>{{ Str::lower($listing->status->label()) }}</strong> by another member. You can save it or post your own request.</x-alert>
                @endif
                <div class="mt-4 flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline"><x-icon name="bookmark" class="size-4" />Save</a>
                    <button type="button" data-dialog-open="report-dialog" class="inline-flex items-center gap-1.5 font-semibold text-red-700 hover:underline"><x-icon name="flag" class="size-4" />Report listing</button>
                </div>

                <section class="mt-6 rounded-xl bg-warm-100 p-5 text-warm-700" aria-labelledby="before-request">
                    <h2 id="before-request" class="flex items-center gap-2 font-bold"><x-icon name="alert" class="size-5" />Before you request</h2>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        <li>Some medicines need a prescription check by an authorised reviewer. You'll be told if this applies.</li>
                        <li>This page is not medical advice. Ask a doctor or pharmacist before taking any medicine.</li>
                        <li>Check the seal, strength and expiry yourself at hand-over.</li>
                    </ul>
                </section>

                <section class="card mt-6 flex items-center gap-4 p-5" aria-label="Listed by">
                    <x-avatar :name="$listing->owner->name" size="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">{{ $listing->owner->name }}</p>
                        <p class="text-sm text-muted">Joined {{ $listing->owner->joined_at->format('F Y') }}</p>
                        @if ($listing->owner->is_verified)<p class="mt-0.5 inline-flex items-center gap-1 text-xs font-semibold text-brand-700"><x-icon name="shield" class="size-4" />Verified member · {{ $listing->owner->completed_count }} completed {{ Str::plural('contribution', $listing->owner->completed_count) }}</p>@endif
                    </div>
                    <a href="{{ route('members.show', $listing->owner->id) }}" class="link shrink-0 text-sm">Profile</a>
                </section>
            </div>
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-16" aria-labelledby="similar">
                <h2 id="similar" class="text-2xl font-bold">Similar listings</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similar as $item)<x-medicine-card :listing="$item" />@endforeach
                </div>
            </section>
        @endif
    </div>

    <x-confirm-dialog id="report-dialog" title="Report this listing" :action="route('reports.store')" confirm="Send report" variant="danger">
        <p>Tell moderators what is wrong. Your report is private; the member won't see who sent it.</p>
        <input type="hidden" name="listing_id" value="{{ $listing->id }}">
        <label for="report-reason" class="label mt-4">Reason</label>
        <select id="report-reason" name="category" required class="field">
            <option value="">Choose a reason</option>
            <option>Expired or wrong expiry date</option><option>Damaged or opened packaging</option><option>Restricted or prohibited medicine</option><option>Offered for sale / asking for money</option><option>Suspicious or misleading</option><option>Other</option>
        </select>
        <label for="report-details" class="label mt-3">Details <span class="font-normal text-muted">(optional)</span></label>
        <textarea id="report-details" name="details" rows="3" maxlength="1000" class="field"></textarea>
    </x-confirm-dialog>
</x-layouts.public>
