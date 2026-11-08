<x-layouts.admin title="Partners">
    <x-page-header title="Partner management" description="Future authorised organisations: clinics, NGOs, pharmacies and CSR sponsors.">
        <x-slot:actions><x-button icon="plus" data-dialog-open="add-partner">Add partner</x-button></x-slot:actions>
    </x-page-header>
    <x-alert type="info" class="mb-5" title="Prototype">No payments are processed. Partners and sponsors never get access to member data and can never bypass safety rules.</x-alert>
    @if ($items->isEmpty())
        <x-empty-state title="No partner organisations yet" icon="building" />
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($items as $p)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3"><span class="grid size-11 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="building" /></span><x-status-badge :status="$p->status" /></div>
                    <p class="mt-4 font-bold">{{ $p->name }}</p>
                    <p class="text-sm text-muted">{{ $p->type }}{{ $p->district ? ' · '.$p->district : '' }}</p>
                    @if ($p->plan)<p class="mt-3 text-sm"><span class="text-muted">Plan:</span> {{ $p->plan }}</p>@endif
                    <x-button variant="neutral" size="sm" class="mt-4" data-dialog-open="partner-{{ $p->id }}">Change status</x-button>
                </div>
                <x-confirm-dialog :id="'partner-'.$p->id" :title="'Status of '.$p->name" :action="route('admin.partners.update', $p)" method="PATCH" confirm="Save" reason>
                    <label for="pstatus-{{ $p->id }}" class="label mt-2">Status</label>
                    <select id="pstatus-{{ $p->id }}" name="status" class="field">@foreach (\App\Enums\PartnerStatus::cases() as $s)<option value="{{ $s->value }}" @selected($p->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                </x-confirm-dialog>
            @endforeach
        </div>
    @endif
    <x-confirm-dialog id="add-partner" title="Add a partner organisation" :action="route('admin.partners.store')" confirm="Add partner">
        <div class="space-y-3 text-ink">
            <x-form.input name="name" label="Organisation name" required maxlength="150" />
            <div class="grid grid-cols-2 gap-3"><x-form.select name="type" label="Type" required :options="\App\Models\Partner::TYPES" /><x-form.input name="district" label="District" maxlength="40" /></div>
            <x-form.input name="plan" label="Plan" maxlength="80" hint="Prototype label only, e.g. Community (sample plan)." />
            <x-form.input name="contact_email" type="email" label="Contact email" maxlength="150" />
        </div>
    </x-confirm-dialog>
</x-layouts.admin>
