<x-layouts.admin title="Requests">
    <x-page-header title="Request management" description="All medicine requests across the platform." />
    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.requests.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => ! $status, 'bg-white ring-1 ring-line' => $status])>All</a>
        @foreach (\App\Enums\RequestStatus::cases() as $s)<a href="{{ route('admin.requests.index', ['status' => $s->value]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => $status === $s, 'bg-white ring-1 ring-line' => $status !== $s])>{{ $s->label() }}</a>@endforeach
    </nav>
    @if ($requests->isEmpty())<x-empty-state title="No requests" icon="inbox" />@endif
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Requests</caption>
            <thead><tr><th scope="col">#</th><th scope="col">Medicine</th><th scope="col">Member</th><th scope="col">District</th><th scope="col">Preference</th><th scope="col">Matches</th><th scope="col">Status</th><th scope="col">Created</th></tr></thead>
            <tbody>
                @foreach ($requests as $r)
                    <tr>
                        <td class="text-muted">{{ $r->id }}</td>
                        <td><span class="font-semibold">{{ $r->name }}</span><span class="block text-xs text-muted">{{ $r->strength }} {{ $r->dosage_form }} · {{ $r->quantity }}</span></td>
                        <td><a href="{{ route('admin.users.show', $r->owner->id) }}" class="hover:text-brand-700">{{ $r->owner->name }}</a></td>
                        <td>{{ $r->district }}</td>
                        <td class="text-xs">{{ $r->preferenceLabel() }}</td>
                        <td>{{ $r->matches_count }}</td>
                        <td><x-status-badge :status="$r->status" /></td>
                        <td class="whitespace-nowrap text-muted">{{ $r->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</x-layouts.admin>
