<x-layouts.admin title="Medicine Catalogue">
    <x-page-header title="Medicine catalogue" description="Verified medicine identities, aliases and eligibility rules. Every listing must match an entry here.">
        <x-slot:actions><x-button icon="plus" data-dialog-open="add-medicine">Add medicine</x-button></x-slot:actions>
    </x-page-header>
    <x-alert type="warning" class="mb-5">Eligibility values are project rules for the prototype. A real deployment must set them with qualified pharmacists against current Bangladesh regulations.</x-alert>
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Medicine catalogue</caption>
            <thead><tr><th scope="col">Brand</th><th scope="col">Generic</th><th scope="col">Strength / form</th><th scope="col">Aliases</th><th scope="col">Eligibility</th><th scope="col">Listings</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($medicines as $m)
                    <tr @class(['opacity-60' => ! $m->is_active])>
                        <td class="font-semibold">{{ $m->brand_name }}@unless ($m->is_active)<span class="block text-xs font-normal text-muted">Inactive</span>@endunless</td>
                        <td>{{ $m->generic_name }}</td>
                        <td class="whitespace-nowrap">{{ $m->strength }} · {{ $m->dosage_form }}</td>
                        <td><div class="flex flex-wrap gap-1">@foreach ($m->aliases as $a)<span class="rounded bg-canvas px-1.5 py-0.5 text-xs">{{ $a->alias }}</span>@endforeach</div></td>
                        <td><x-status-badge :status="$m->eligibility" /></td>
                        <td>{{ $m->listings_count }}</td>
                        <td class="text-right"><x-button variant="neutral" size="sm" icon="edit" data-dialog-open="edit-medicine-{{ $m->id }}">Edit</x-button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach ($medicines as $m)
        <x-confirm-dialog :id="'edit-medicine-'.$m->id" :title="'Edit '.$m->brand_name" :action="route('admin.medicines.update', $m)" method="PATCH" confirm="Save changes" reason reason-label="Reason for the change (audit log)">
            <div class="space-y-3 text-ink">
                <label for="elig-{{ $m->id }}" class="label">Eligibility</label>
                <select id="elig-{{ $m->id }}" name="eligibility" class="field">@foreach (\App\Enums\MedicineEligibility::cases() as $e)<option value="{{ $e->value }}" @selected($m->eligibility === $e)>{{ $e->label() }}</option>@endforeach</select>
                <label for="active-{{ $m->id }}" class="label">Status</label>
                <select id="active-{{ $m->id }}" name="is_active" class="field"><option value="1" @selected($m->is_active)>Active</option><option value="0" @selected(! $m->is_active)>Inactive (cannot be chosen for new listings)</option></select>
                <label for="aliases-{{ $m->id }}" class="label">Aliases <span class="font-normal text-muted">(comma separated)</span></label>
                <input id="aliases-{{ $m->id }}" name="aliases" value="{{ $m->aliases->pluck('alias')->implode(', ') }}" maxlength="500" class="field">
            </div>
        </x-confirm-dialog>
    @endforeach

    <x-confirm-dialog id="add-medicine" title="Add a catalogue entry" :action="route('admin.medicines.store')" confirm="Add medicine">
        <div class="space-y-3 text-ink">
            <x-form.input name="brand_name" label="Brand name" required maxlength="100" />
            <x-form.input name="generic_name" label="Generic name" required maxlength="100" />
            <div class="grid grid-cols-2 gap-3"><x-form.input name="strength" label="Strength" required maxlength="30" placeholder="e.g. 500 mg" /><x-form.select name="dosage_form" label="Form" required :options="\App\Http\Requests\MedicineRequestRequest::FORMS" /></div>
            <x-form.select name="eligibility" label="Eligibility" required :options="collect(\App\Enums\MedicineEligibility::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])->all()" />
            <x-form.input name="aliases" label="Aliases" maxlength="500" hint="Comma separated, e.g. Napa, Paracetamol 500" />
        </div>
    </x-confirm-dialog>
</x-layouts.admin>
