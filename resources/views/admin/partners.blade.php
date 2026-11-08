<x-layouts.admin title="Partners">
    <x-page-header title="Partner management" description="Future authorised organisations: clinics, NGOs, pharmacies and CSR sponsors." />
    <x-alert type="info" class="mb-5" title="Prototype">Partner organisations and plans below are fictional. No payments are processed. Sponsors can never bypass safety rules or access personal data.</x-alert>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($items as $p)
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3"><span class="grid size-11 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="building" /></span><x-status-badge :status="$p->status" /></div>
                <p class="mt-4 font-bold">{{ $p->name }}</p>
                <p class="text-sm text-muted">{{ $p->type }} · {{ $p->district }}</p>
                <p class="mt-3 text-sm"><span class="text-muted">Plan:</span> {{ $p->plan }}</p>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
