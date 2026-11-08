<x-layouts.admin title="Medicine Catalogue">
    <x-page-header title="Medicine catalogue" description="Verified medicine identities, aliases and eligibility rules. Listings must match an entry here.">
        <x-slot:actions><x-button icon="plus" data-dialog-open="add-medicine">Add medicine</x-button></x-slot:actions>
    </x-page-header>
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Medicine catalogue</caption>
            <thead><tr><th scope="col">Brand</th><th scope="col">Generic</th><th scope="col">Strength / form</th><th scope="col">Aliases</th><th scope="col">Eligibility</th><th scope="col">Listings</th></tr></thead>
            <tbody>
                @foreach ($medicines as $m)
                    <tr>
                        <td class="font-semibold">{{ $m->brand }}</td>
                        <td>{{ $m->generic }}</td>
                        <td class="whitespace-nowrap">{{ $m->strength }} · {{ $m->form }}</td>
                        <td><div class="flex flex-wrap gap-1">@foreach ($m->aliases as $a)<span class="rounded bg-canvas px-1.5 py-0.5 text-xs">{{ $a }}</span>@endforeach</div></td>
                        <td><x-status-badge :status="$m->eligibility" /></td>
                        <td>{{ $counts[$m->id] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-confirm-dialog id="add-medicine" title="Add a catalogue entry" :action="route('admin.medicines.store')" confirm="Add medicine">
        <div class="space-y-3 text-ink">
            <x-form.input name="brand" label="Brand name" required maxlength="100" />
            <x-form.input name="generic" label="Generic name" required maxlength="100" />
            <div class="grid grid-cols-2 gap-3"><x-form.input name="strength" label="Strength" required maxlength="30" /><x-form.select name="form" label="Form" required :options="\App\Support\SampleData::dosageForms()" /></div>
            <x-form.select name="eligibility" label="Eligibility" required :options="collect(\App\Enums\MedicineEligibility::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])->all()" />
        </div>
    </x-confirm-dialog>
</x-layouts.admin>
