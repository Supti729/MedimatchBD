<x-layouts.admin title="Audit Logs">
    <x-page-header title="Audit logs" description="Append-only record of administrative and reviewer actions. Entries cannot be edited or deleted." />
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Audit log</caption>
            <thead><tr><th scope="col">When</th><th scope="col">Actor</th><th scope="col">Action</th><th scope="col">Target</th><th scope="col">Reason</th><th scope="col">IP</th></tr></thead>
            <tbody>
                @foreach ($items->sortByDesc('created_at') as $log)
                    <tr>
                        <td class="whitespace-nowrap text-muted">{{ $log->created_at->format('d M Y, g:i a') }}</td>
                        <td class="whitespace-nowrap">{{ $log->actor->name }}<span class="block text-xs capitalize text-muted">{{ $log->actor->role }}</span></td>
                        <td><code class="rounded bg-canvas px-2 py-0.5 text-xs">{{ $log->action }}</code></td>
                        <td>{{ $log->target }}</td>
                        <td class="text-muted">{{ $log->reason }}</td>
                        <td class="font-mono text-xs text-muted">{{ $log->ip }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
