@php $r = $medicineRequest; $closed = in_array($r->status, [\App\Enums\RequestStatus::Completed, \App\Enums\RequestStatus::Cancelled, \App\Enums\RequestStatus::Expired], true); @endphp
<x-layouts.app :title="'Request: '.$r->name">
    <x-page-header :title="$r->name" :description="$r->generic.' · '.$r->strength.' '.$r->form" :back="route('requests.index')" back-label="My requests">
        <x-slot:actions>
            @unless ($closed)
                <x-button :href="route('requests.edit', $r->id)" variant="neutral" icon="edit">Edit</x-button>
                <x-button variant="danger-outline" icon="x" data-dialog-open="cancel-request">Cancel request</x-button>
            @endunless
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            @if ($r->status === \App\Enums\RequestStatus::VerificationPending)
                <x-alert type="warning" title="Verification pending">An authorised reviewer is checking your prescription. The donor will only see whether it passed. <a href="{{ route('verification.index') }}" class="font-semibold underline">Open Verification Center</a></x-alert>
            @endif
            @if ($needsPrescription && ! $r->verificationSubmissions->contains(fn ($v) => $v->status === \App\Enums\VerificationStatus::Verified) && $r->status->isOpen() && $r->status !== \App\Enums\RequestStatus::VerificationPending)
                <x-alert type="warning" title="Prescription check needed">{{ $r->name }} needs a prescription check before a donor can reserve it for you. <a href="{{ route('verification.index', ['request' => $r->id]) }}" class="font-semibold underline">Upload it privately</a> — only an authorised reviewer will see it.</x-alert>
            @endif
            <x-section title="Potential matches" description="Eligible listings with the same medicine, strength and form, ranked by relevance.">
                @if ($matches->isEmpty())
                    <x-empty-state title="No matches yet" icon="sparkles">We'll notify you as soon as a relevant listing appears near you.<x-slot:actions><x-button :href="route('medicines.search', ['q' => $r->generic])" variant="neutral" icon="search">Search manually</x-button></x-slot:actions></x-empty-state>
                @else
                    <div class="space-y-3">
                        @foreach ($matches as $match)<x-medicine-card :listing="$match->listing" layout="list" :relevance="$match->score" />@endforeach
                    </div>
                    <p class="mt-4 text-xs text-muted">Relevance comes from rule-based matching (AI-assisted ranking arrives in Part 3). It is not medical advice or a statement that medicines are equivalent.</p>
                @endif
            </x-section>
        </div>
        <aside class="space-y-4">
            <x-section title="Request details">
                <div class="mb-4"><x-status-badge :status="$r->status" /></div>
                <x-info-list :items="['Quantity' => $r->quantity, 'Area' => $r->upazila.', '.$r->district, 'Preference' => $r->preferenceLabel(), 'Created' => $r->created_at->format('d M Y'), 'Expires' => $r->expires_at?->format('d M Y') ?? '—']" />
                @if ($r->notes)<p class="mt-4 text-sm text-muted">{{ $r->notes }}</p>@endif
            </x-section>
        </aside>
    </div>
    <x-confirm-dialog id="cancel-request" title="Cancel this request?" :action="route('requests.cancel', $r->id)" method="PATCH" confirm="Cancel request" variant="danger">You'll stop receiving matches. Any active conversations stay available in Messages.</x-confirm-dialog>
</x-layouts.app>
