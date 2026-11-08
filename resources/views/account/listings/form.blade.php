@php
    $editing = (bool) $listing;
    $E = \App\Enums\MedicineEligibility::class;
@endphp
<x-layouts.app :title="$editing ? 'Edit listing' : 'Donate medicine'">
    <x-page-header :title="$editing ? 'Edit '.$listing->name : 'Donate medicine'" :description="$editing ? 'Changes to key details may send the listing back for review.' : 'List a sealed, in-date medicine you no longer need. It takes about two minutes.'" :back="route('listings.index')" back-label="My listings" />

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ $editing ? route('listings.update', $listing->id) : route('listings.store') }}" enctype="multipart/form-data" data-loading class="space-y-6">
            @csrf
            @if ($editing) @method('PATCH') @endif

            <x-section title="1. Medicine" description="Choose from the verified medicine catalogue so others can find it reliably.">
                <div class="space-y-5">
                    <div>
                        <label for="medicine_id" class="label">Medicine <span class="text-red-700" aria-hidden="true">*</span></label>
                        <select id="medicine_id" name="medicine_id" required class="field" aria-describedby="medicine-help">
                            <option value="">Select a medicine</option>
                            @foreach ($catalogue->groupBy(fn ($m) => $m->eligibility->label()) as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach ($items as $m)
                                        <option value="{{ $m->id }}" @selected(old('medicine_id', $listing?->medicine->id) == $m->id) @disabled($m->eligibility === $E::NotAllowed)>{{ $m->brand }} — {{ $m->generic }} {{ $m->strength }} {{ $m->form }}{{ $m->eligibility === $E::NotAllowed ? ' (cannot be listed)' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p id="medicine-help" class="hint">Can't find it? It may not be in the catalogue yet. <a href="{{ route('contact') }}" class="link">Suggest a medicine</a>.</p>
                    </div>
                    <x-alert type="warning">Medicines marked <strong>Verification Required</strong> are reviewed before they appear in search. Medicines marked <strong>Not Allowed</strong> cannot be listed.</x-alert>
                </div>
            </x-section>

            <x-section title="2. Quantity, expiry and condition">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="quantity" type="number" label="Quantity" :value="$listing ? (int) $listing->quantity : null" required min="1" max="500" />
                    <x-form.select name="unit" label="Unit" required :options="['tablets' => 'Tablets', 'capsules' => 'Capsules', 'bottles' => 'Bottles', 'tubes' => 'Tubes', 'inhalers' => 'Inhalers', 'other' => 'Other']" :value="$listing ? \Illuminate\Support\Str::afterLast($listing->quantity, ' ') : null" placeholder="Select unit" />
                    <x-form.input name="expires_at" type="month" label="Expiry month" :value="$listing?->expires_at->format('Y-m')" required :min="now()->addMonth()->format('Y-m')" hint="Must be at least one month away." />
                    <x-form.select name="packaging" label="Packaging condition" required placeholder="Select condition" :value="$listing?->packaging" :options="['Sealed, undamaged' => 'Sealed, undamaged', 'Sealed bottle' => 'Sealed bottle', 'Outer box opened, strips sealed' => 'Outer box opened, strips/blisters sealed']" hint="Opened or damaged medicine cannot be listed." />
                    <x-form.input name="batch_number" label="Batch number" maxlength="40" hint="Visible to reviewers only, never to other members." class="sm:col-span-2" />
                </div>
            </x-section>

            <x-section title="3. Photos" description="Show the packaging, name, strength and expiry date clearly. Don't include personal information.">
                <label for="photos" class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed border-line bg-canvas px-6 py-8 text-center hover:border-brand-600">
                    <x-icon name="upload" class="size-8 text-brand-600" />
                    <span class="font-semibold">Choose up to 4 photos</span>
                    <span class="text-sm text-muted">JPG, PNG or WebP, up to 4 MB each</span>
                </label>
                <input id="photos" name="photos[]" type="file" multiple accept="image/jpeg,image/png,image/webp" data-image-preview="photo-preview" data-max="4" class="sr-only">
                <div id="photo-preview" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-live="polite"></div>
            </x-section>

            <x-section title="4. Pick-up area" description="Only the upazila and district are shown publicly. Arrange the exact place in private chat.">
                <x-form.location :division="$listing?->division" :district="$listing?->district" :upazila="$listing?->upazila" required />
            </x-section>

            <x-section title="5. Listing type">
                <fieldset class="grid gap-3 sm:grid-cols-2">
                    <legend class="sr-only">Listing type</legend>
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 has-checked:border-brand-600 has-checked:bg-brand-50">
                        <input type="radio" name="type" value="donation" required @checked(old('type', $listing?->type->value ?? 'donation') === 'donation') class="mt-1 size-5 accent-brand-600">
                        <span><span class="font-semibold">Donation</span><span class="block text-sm text-muted">Give the medicine free of charge.</span></span>
                    </label>
                    <label class="flex gap-3 rounded-xl border border-line bg-canvas p-4 opacity-70">
                        <input type="radio" name="type" value="affordable_access" disabled class="mt-1 size-5">
                        <span><span class="font-semibold">Affordable Access</span><span class="block text-sm text-muted">Only for approved partner programmes. Not available to individual members.</span></span>
                    </label>
                </fieldset>
            </x-section>

            <x-section title="6. Notes and declaration">
                <x-form.textarea name="description" label="Notes for requesters" :value="$listing?->description" rows="3" maxlength="500" hint="e.g. storage conditions. No medical advice, phone numbers or addresses." />
                <div class="mt-5 space-y-3 rounded-xl bg-canvas p-4">
                    <x-form.checkbox required name="confirm_sealed" label="The medicine is sealed, undamaged and stored as the label instructs." />
                    <x-form.checkbox required name="confirm_expiry" label="The expiry date I entered matches the packaging." />
                    <x-form.checkbox required name="confirm_free" label="I am not asking for payment or anything in return." />
                </div>
            </x-section>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button :href="route('listings.index')" variant="neutral">Cancel</x-button>
                <x-button type="submit" name="intent" value="draft" variant="secondary" formnovalidate>Save as draft</x-button>
                <x-button type="submit" name="intent" value="submit" icon="check" data-loading-text="Submitting…">{{ $editing ? 'Save changes' : 'Submit listing' }}</x-button>
            </div>
        </form>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:h-fit">
            <div class="card p-5">
                <p class="flex items-center gap-2 font-bold"><x-icon name="check" class="text-brand-600" />Can be listed</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-muted"><li>Sealed and in date</li><li>In the verified catalogue</li><li>Stored correctly</li></ul>
                <p class="mt-4 flex items-center gap-2 font-bold"><x-icon name="x" class="text-red-700" />Cannot be listed</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-muted"><li>Expired, opened or damaged</li><li>Restricted or controlled medicines</li><li>Anything for sale or profit</li></ul>
                <a href="{{ route('safety') }}" class="link mt-4 inline-block text-sm">Full safety guidelines</a>
            </div>
            <x-alert type="info">After you submit, your listing is checked against the catalogue rules. You'll get a notification when it goes live.</x-alert>
        </aside>
    </div>
</x-layouts.app>
