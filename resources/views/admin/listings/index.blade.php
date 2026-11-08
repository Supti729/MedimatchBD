<x-layouts.admin title="Listings">
    <x-page-header title="Listing management" description="Review, approve, reject and block listings." />
    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.listings.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => ! $status, 'bg-white ring-1 ring-line' => $status])>All</a>
        @foreach ([\App\Enums\ListingStatus::PendingReview, \App\Enums\ListingStatus::Available, \App\Enums\ListingStatus::Reserved, \App\Enums\ListingStatus::Blocked, \App\Enums\ListingStatus::Completed] as $s)
            <a href="{{ route('admin.listings.index', ['status' => $s->value]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => $status === $s, 'bg-white ring-1 ring-line' => $status !== $s])>{{ $s->label() }}</a>
        @endforeach
    </nav>
    @if ($listings->isEmpty())
        <x-empty-state title="No listings with this status" icon="list" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Listings</caption>
                <thead><tr><th scope="col">#</th><th scope="col">Medicine</th><th scope="col">Owner</th><th scope="col">District</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Created</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($listings as $l)
                        <tr>
                            <td class="text-muted">{{ $l->id }}</td>
                            <td><span class="font-semibold">{{ $l->name }}</span><span class="block text-xs text-muted">{{ $l->strength }} {{ $l->form }} · {{ $l->quantity }}</span></td>
                            <td>{{ $l->owner->name }}</td>
                            <td>{{ $l->district }}</td>
                            <td><x-status-badge :status="$l->type" size="xs" /></td>
                            <td><x-status-badge :status="$l->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $l->created_at->format('d M Y') }}</td>
                            <td class="text-right"><x-button :href="route('admin.listings.show', $l->id)" variant="neutral" size="sm">Review</x-button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
