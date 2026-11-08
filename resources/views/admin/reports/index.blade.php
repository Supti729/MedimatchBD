<x-layouts.admin title="Reports">
    <x-page-header title="Reports" description="Member reports about listings, users and messages." />
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
                        <td>{{ $r->reason }}</td>
                        <td>{{ $r->reporter->name }}</td>
                        <td><x-status-badge :status="$r->status" /></td>
                        <td class="whitespace-nowrap text-muted">{{ $r->created_at->diffForHumans() }}</td>
                        <td class="text-right"><x-button :href="route('admin.reports.show', $r->id)" variant="neutral" size="sm">Open</x-button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
