<x-layouts.public :title="$listing->name.' · '.$listing->generic">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <nav aria-label="Breadcrumb" class="text-sm text-muted"><a href="{{ route('medicines.index') }}" class="hover:underline">Find Medicine</a> <span aria-hidden="true">/</span> {{ $listing->name }}</nav>

        <div class="mt-5 grid gap-8 lg:grid-cols-2">
            {{-- Packaging photos --}}
            <div>
                @if ($listing->images->isNotEmpty())
                    <img src="{{ $listing->images->first()->url }}" alt="Packaging photo of {{ $listing->name }}" class="aspect-[4/3] w-full rounded-2xl border border-line object-cover">
                    @if ($listing->images->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach ($listing->images->skip(1) as $image)
                                <a href="{{ $image->url }}" target="_blank" rel="noopener"><img src="{{ $image->url }}" alt="Another photo of {{ $listing->name }}" class="aspect-square w-full rounded-lg border border-line object-cover"></a>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="grid aspect-[4/3] place-items-center rounded-2xl border border-line bg-brand-50 text-center text-muted">
                        <div><x-icon name="camera" class="mx-auto size-12 text-brand-600" /><p class="mt-2 text-sm">No photos were added</p></div>
                    </div>
                @endif
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
                    'Available quantity' => $listing->quantity_label,
                    'Expiry' => $listing->expires_at->format('F Y'),
                    'Packaging' => $listing->packaging,
                    'General area' => ($listing->owner->privacySetting('show_upazila') === false ? '' : $listing->upazila.', ').$listing->district,
                    'Listed' => ($listing->published_at ?? $listing->created_at)->diffForHumans(),
                ]" />
                <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-line px-4 py-3 text-sm"><span class="text-muted">Listing status</span><x-status-badge :status="$listing->status" /></div>
                <p class="mt-2 text-xs text-muted">The exact address is never shown. Batch numbers are visible to reviewers only.</p>

                @php $canRequest = auth()->check() && Gate::allows('request', $listing); @endphp
                @if (auth()->id() === $listing->user_id)
                    <x-alert type="info" class="mt-6">This is your listing. <a href="{{ route('listings.index') }}" class="font-semibold underline">Manage it in My Listings</a>.</x-alert>
                @elseif ($existingConversation)
                    <div class="mt-6"><x-button :href="route('messages.show', $existingConversation)" size="lg" icon="message" class="w-full sm:w-auto">Continue the conversation</x-button></div>
                @elseif (in_array($listing->status, [\App\Enums\ListingStatus::Available, \App\Enums\ListingStatus::Requested], true))
                    @auth
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <x-button size="lg" icon="inbox" data-dialog-open="request-dialog" :disabled="! $canRequest">Request This Medicine</x-button>
                            <x-button size="lg" variant="secondary" icon="message" data-dialog-open="chat-dialog" :disabled="! $canRequest || $listing->owner->privacySetting('who_can_message') === 'none'">Start Chat</x-button>
                        </div>
                        @if ($needsVerification)
                            <p class="mt-2 text-sm text-warm-700">This medicine needs a verified prescription check before the donor can reserve it. <a href="{{ route('verification.index') }}" class="font-semibold underline">Verification Center</a></p>
                        @endif
                    @else
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <x-button :href="route('login')" size="lg" icon="inbox">Request This Medicine</x-button>
                            <x-button :href="route('login')" size="lg" variant="secondary" icon="message">Start Chat</x-button>
                        </div>
                        <p class="mt-2 text-sm text-muted">You need to <a href="{{ route('login') }}" class="link">log in</a> or <a href="{{ route('register') }}" class="link">register</a> to request or chat.</p>
                    @endauth
                    @if ($listing->status === \App\Enums\ListingStatus::Requested)<p class="mt-2 text-sm text-muted">Another member has also asked for this medicine. The donor decides who receives it.</p>@endif
                @else
                    <x-alert type="warning" class="mt-6">This medicine is currently <strong>{{ Str::lower($listing->status->label()) }}</strong> for another member. You can save it or post your own request.</x-alert>
                @endif
                @if (auth()->id() !== $listing->user_id)
                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        @auth
                            @if ($isSaved)
                                <form method="POST" action="{{ route('saved.destroy', $listing) }}">@csrf @method('DELETE')<button class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline"><x-icon name="bookmark" class="size-4" />Saved — remove</button></form>
                            @else
                                <form method="POST" action="{{ route('saved.store', $listing) }}">@csrf<button class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline"><x-icon name="bookmark" class="size-4" />Save</button></form>
                            @endif
                            <button type="button" data-dialog-open="report-dialog" class="inline-flex items-center gap-1.5 font-semibold text-red-700 hover:underline"><x-icon name="flag" class="size-4" />Report listing</button>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:underline"><x-icon name="bookmark" class="size-4" />Log in to save or report</a>
                        @endauth
                    </div>
                @endif

                <section class="mt-6 rounded-xl bg-warm-100 p-5 text-warm-700" aria-labelledby="before-request">
                    <h2 id="before-request" class="flex items-center gap-2 font-bold"><x-icon name="alert" class="size-5" />Before you request</h2>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        <li>Some medicines need a prescription check by an authorised reviewer. You'll be told if this applies.</li>
                        <li>This page is not medical advice. Ask a doctor or pharmacist before taking any medicine.</li>
                        <li>Check the seal, strength and expiry yourself at hand-over.</li>
                    </ul>
                </section>

                <section class="card mt-6 flex items-center gap-4 p-5" aria-label="Listed by">
                    <x-avatar :user="$listing->owner" size="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">{{ $listing->owner->name }}</p>
                        <p class="text-sm text-muted">Joined {{ $listing->owner->joined_at->format('F Y') }}</p>
                        <p class="mt-0.5 inline-flex flex-wrap items-center gap-1 text-xs font-semibold text-brand-700">
                            @if ($listing->owner->is_verified)<x-icon name="shield" class="size-4" />Identity verified ·@endif
                            @if ($listing->owner->privacySetting('show_contributions')){{ $listing->owner->completed_count }} completed {{ Str::plural('contribution', $listing->owner->completed_count) }}@endif
                        </p>
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

    @auth
        <x-confirm-dialog id="report-dialog" title="Report this listing" :action="route('reports.store')" confirm="Send report" variant="danger">
            <p>Tell moderators what is wrong. Your report is private; the member won't see who sent it.</p>
            <input type="hidden" name="listing_id" value="{{ $listing->id }}">
            <label for="report-reason" class="label mt-4">Reason</label>
            <select id="report-reason" name="category" required class="field">
                <option value="">Choose a reason</option>
                @foreach (\App\Models\Report::CATEGORIES as $category)<option>{{ $category }}</option>@endforeach
            </select>
            <label for="report-details" class="label mt-3">Details <span class="font-normal text-muted">(optional)</span></label>
            <textarea id="report-details" name="details" rows="3" maxlength="1000" class="field"></textarea>
        </x-confirm-dialog>

        @if ($canRequest)
            <x-confirm-dialog id="request-dialog" :title="'Request '.$listing->name" :action="route('medicines.request', $listing)" confirm="Send request">
                <p>{{ $listing->owner->name }} will be notified and a private chat will open. Your contact details stay hidden.</p>
                @if ($myRequests->isNotEmpty())
                    <label for="link-request" class="label mt-4">Link to one of your requests <span class="font-normal text-muted">(optional)</span></label>
                    <select id="link-request" name="medicine_request_id" class="field">
                        <option value="">Don't link</option>
                        @foreach ($myRequests as $r)<option value="{{ $r->id }}">{{ $r->medicine_name }} · {{ $r->quantity }}</option>@endforeach
                    </select>
                @endif
                <label for="request-message" class="label mt-3">Message <span class="font-normal text-muted">(optional)</span></label>
                <textarea id="request-message" name="message" rows="3" maxlength="1000" class="field" placeholder="e.g. I can collect from a public place nearby this weekend."></textarea>
                <p class="hint">Don't include phone numbers, addresses or medical details.</p>
            </x-confirm-dialog>
            <x-confirm-dialog id="chat-dialog" :title="'Message '.$listing->owner->name" :action="route('messages.start', $listing)" confirm="Send message">
                <p>Ask a question about this listing before requesting it.</p>
                <label for="chat-message" class="label mt-4">Message</label>
                <textarea id="chat-message" name="message" rows="3" required maxlength="1000" class="field"></textarea>
            </x-confirm-dialog>
        @endif
    @endauth
</x-layouts.public>
