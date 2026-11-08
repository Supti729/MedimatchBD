@php $editing = (bool) $medicineRequest; $r = $medicineRequest; @endphp
<x-layouts.app :title="$editing ? 'Edit request' : 'Request medicine'">
    <x-page-header :title="$editing ? 'Edit request: '.$r->name : 'Request medicine'" description="Tell us what you need. Only your general area is shown, and you'll be notified about relevant listings." :back="$editing ? route('requests.show', $r->id) : route('requests.index')" :back-label="$editing ? 'Back to request' : 'My requests'" />
    <form method="POST" action="{{ $editing ? route('requests.update', $r->id) : route('requests.store') }}" data-loading class="max-w-3xl space-y-6">
        @csrf
        @if ($editing) @method('PATCH') @endif
        <x-section title="Medicine">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="medicine_id" class="label">Medicine from catalogue <span class="font-normal text-muted">(recommended)</span></label>
                    <select id="medicine_id" name="medicine_id" class="field">
                        <option value="">Not sure / not listed</option>
                        @foreach ($catalogue->reject(fn ($m) => $m->eligibility === \App\Enums\MedicineEligibility::NotAllowed) as $m)
                            <option value="{{ $m->id }}" @selected(old('medicine_id', $r?->medicine->id) == $m->id)>{{ $m->brand }} — {{ $m->generic }} {{ $m->strength }} {{ $m->form }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Choosing from the catalogue gives the most accurate matches and fills in the name, strength and form for you.</p>
                </div>
                <x-form.input name="medicine_name" label="Medicine or brand name" :value="$r?->medicine_name" required maxlength="100" placeholder="e.g. Napa 500" />
                <x-form.input name="generic_name" label="Generic name" :value="$r?->generic_name" maxlength="100" placeholder="e.g. Paracetamol" />
                <x-form.input name="strength" label="Strength" :value="$r?->strength" required maxlength="30" placeholder="e.g. 500 mg" />
                <x-form.select name="dosage_form" label="Dosage form" :options="\App\Http\Requests\MedicineRequestRequest::FORMS" :value="$r?->dosage_form" placeholder="Select form" required />
                <x-form.input name="quantity" label="Quantity needed" :value="$r?->quantity" required maxlength="40" placeholder="e.g. 10 tablets" />
                <x-form.select name="preference" label="Assistance preference" required :value="$r?->preference ?? 'either'" :options="['donation' => 'Donation only', 'either' => 'Donation or Affordable Access', 'affordable_access' => 'Affordable Access only']" />
            </div>
        </x-section>
        <x-section title="Area" description="Matches near this area are shown first.">
            <x-form.location :division="$r?->division ?? $me->division" :district="$r?->district ?? $me->district" :upazila="$r?->upazila ?? $me->upazila" required />
        </x-section>
        <x-section title="Additional information">
            <x-form.textarea name="notes" label="Notes" :value="$r?->notes" rows="3" maxlength="500" hint="Non-sensitive details only. Don't include diagnoses, prescriptions, phone numbers or addresses." />
            <x-alert type="info" class="mt-4">If this medicine needs a prescription check, you'll be asked to upload it privately in the Verification Center. It goes only to an authorised reviewer.</x-alert>
        </x-section>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button :href="$editing ? route('requests.show', $r->id) : route('requests.index')" variant="neutral">Cancel</x-button>
            <x-button type="submit" icon="check" data-loading-text="Saving…">{{ $editing ? 'Save changes' : 'Post request' }}</x-button>
        </div>
    </form>
</x-layouts.app>
