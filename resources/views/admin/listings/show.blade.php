<x-layouts.admin :title="'Review '.$listing->name">
    <x-page-header :title="'Listing #'.$listing->id.': '.$listing->name" :back="route('admin.listings.index')" back-label="All listings">
        <x-slot:actions>
            @if ($listing->status === \App\Enums\ListingStatus::PendingReview)
                <x-button icon="check" data-dialog-open="approve">Approve</x-button>
                <x-button variant="danger-outline" icon="x" data-dialog-open="reject">Reject</x-button>
            @endif
            @if ($listing->status->canTransitionTo(\App\Enums\ListingStatus::Blocked))<x-button variant="danger" icon="alert" data-dialog-open="block">Block</x-button>@endif
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-section title="Listing details">
                <div class="mb-4 flex flex-wrap gap-2"><x-status-badge :status="$listing->status" /><x-status-badge :status="$listing->type" /><x-status-badge :status="$listing->eligibility" /></div>
                <x-info-list :items="['Medicine' => $listing->name.' — '.$listing->generic, 'Strength / form' => $listing->strength.' '.$listing->form, 'Quantity' => $listing->quantity_label, 'Expiry' => $listing->expires_at->format('F Y'), 'Packaging' => $listing->packaging, 'Batch number' => $listing->batch_number ?: 'Not provided', 'Area' => $listing->upazila.', '.$listing->district.', '.$listing->division, 'Owner' => $listing->owner->name, 'Created' => $listing->created_at->format('d M Y, g:i a')]" />
                @if ($listing->description)<p class="mt-4 text-sm text-muted">{{ $listing->description }}</p>@endif
                @if ($listing->review_note)<p class="mt-4 rounded-lg bg-canvas px-3 py-2 text-sm"><strong>Last decision:</strong> {{ $listing->review_note }} <span class="text-muted">— {{ $listing->reviewer?->name }}, {{ $listing->reviewed_at?->format('d M Y') }}</span></p>@endif
            </x-section>
            <x-section title="Photos">
                @if ($listing->images->isEmpty())<p class="text-sm text-muted">No photos were uploaded.</p>@else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">@foreach ($listing->images as $image)<a href="{{ $image->url }}" target="_blank" rel="noopener"><img src="{{ $image->url }}" alt="Listing photo" class="aspect-square w-full rounded-lg border border-line object-cover"></a>@endforeach</div>
                @endif
            </x-section>
        </div>
        <aside class="space-y-6">
            <x-section title="Catalogue check">
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><x-icon name="check" class="size-5 text-green-700" />Matches catalogue entry #{{ $listing->medicine->id }} ({{ $listing->medicine->brand_name }})</li>
                    <li class="flex gap-2"><x-icon name="{{ $listing->owner->is_verified ? 'check' : 'info' }}" class="size-5 {{ $listing->owner->is_verified ? 'text-green-700' : 'text-brand-700' }}" />Owner identity {{ $listing->owner->is_verified ? 'verified' : 'not verified' }} · member since {{ $listing->owner->created_at->format('M Y') }}</li>
                    <li class="flex gap-2"><x-icon name="{{ $listing->expires_at->isFuture() ? 'check' : 'x' }}" class="size-5 {{ $listing->expires_at->isFuture() ? 'text-green-700' : 'text-red-700' }}" />Expiry in the future</li>
                    <li class="flex gap-2"><x-icon name="info" class="size-5 text-brand-700" />Eligibility: {{ $listing->eligibility->label() }}</li>
                </ul>
            </x-section>
            <x-section title="Reports ({{ $reports->count() }})" :padded="false">
                <ul class="divide-y divide-line text-sm">
                    @forelse ($reports as $r)<li><a href="{{ route('admin.reports.show', $r) }}" class="flex justify-between gap-2 px-5 py-3 hover:bg-canvas"><span>{{ $r->category }}</span><x-status-badge :status="$r->status" size="xs" /></a></li>@empty<li class="px-5 py-4 text-muted">No reports.</li>@endforelse
                </ul>
            </x-section>
        </aside>
    </div>
    @foreach (array_filter(['approve' => ['Approve this listing?', 'Approve', 'primary', 'It becomes visible in search and the owner is notified.'], 'reject' => ['Reject this listing?', 'Reject', 'danger', 'The owner is notified with your reason and can edit and resubmit.'], 'block' => ['Block this listing?', 'Block listing', 'danger', 'The listing is removed from search immediately. Use for unsafe or prohibited medicine.']], fn ($v, $k) => $listing->status->canTransitionTo(['approve' => \App\Enums\ListingStatus::Available, 'reject' => \App\Enums\ListingStatus::Rejected, 'block' => \App\Enums\ListingStatus::Blocked][$k]), ARRAY_FILTER_USE_BOTH) as $key => [$title, $confirm, $variant, $text])
        <x-confirm-dialog :id="$key" :title="$title" :action="route('admin.listings.decision', $listing->id)" method="PATCH" :confirm="$confirm" :variant="$variant" reason>{{ $text }}<input type="hidden" name="decision" value="{{ $key }}"></x-confirm-dialog>
    @endforeach
</x-layouts.admin>
