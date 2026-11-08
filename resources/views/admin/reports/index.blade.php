<x-layouts.admin title="Reports">
    <x-page-header title="Reports" description="Member reports about listings, users and conversations." />
    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => ! $status, 'bg-white ring-1 ring-line' => $status])>All</a>
        @foreach (\App\Enums\ReportStatus::cases() as $s)<a href="{{ route('admin.reports.index', ['status' => $s->value]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => $status === $s, 'bg-white ring-1 ring-line' => $status !== $s])>{{ $s->label() }}</a>@endforeach
    </nav>
    @if ($reports->isEmpty())
        <x-empty-state title="No reports" icon="flag" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Reports</caption>
                <thead><tr><th scope="col">#</th><th scope="col">Type</th><th scope="col">About</th><th scope="col">Reason</th><th scope="col">Reported by</th><th scope="col">Status</th><th scope="col">Received</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($reports as $r)
                        <tr>
                            <td class="text-muted">{{ $r->id }}</td>
                            <td>{{ $r->target_type }}</td>
                            <td>{{ $r->target_label }}</td>
                            <td>{{ $r->category }}</td>
                            <td>{{ $r->reporter->name }}</td>
                            <td><x-status-badge :status="$r->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $r->created_at->diffForHumans() }}</td>
                            <td class="text-right"><x-button :href="route('admin.reports.show', $r)" variant="neutral" size="sm">Open</x-button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $reports->links() }}
    @endif
</x-layouts.admin>
