<x-layouts.admin title="Audit Logs">
    <x-page-header title="Audit logs" description="Append-only record of administrative and reviewer actions. Entries cannot be edited or deleted." />
    <nav aria-label="Filter by area" class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.audit-logs.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => ! $action, 'bg-white ring-1 ring-line' => $action])>All</a>
        @foreach ($actions as $a)<a href="{{ route('admin.audit-logs.index', ['action' => $a]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold capitalize', 'bg-brand-600 text-white' => $action === $a, 'bg-white ring-1 ring-line' => $action !== $a])>{{ $a }}</a>@endforeach
    </nav>
    @if ($items->isEmpty())
        <x-empty-state title="No audit entries" icon="file" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Audit log</caption>
                <thead><tr><th scope="col">When</th><th scope="col">Actor</th><th scope="col">Action</th><th scope="col">Target</th><th scope="col">Reason</th><th scope="col">IP</th></tr></thead>
                <tbody>
                    @foreach ($items as $log)
                        <tr>
                            <td class="whitespace-nowrap text-muted">{{ $log->created_at->format('d M Y, g:i a') }}</td>
                            <td class="whitespace-nowrap">{{ $log->actor?->name ?? 'System' }}@if ($log->actor)<span class="block text-xs text-muted">{{ $log->actor->role->label() }}</span>@endif</td>
                            <td><code class="rounded bg-canvas px-2 py-0.5 text-xs">{{ $log->action }}</code></td>
                            <td>{{ $log->target }}</td>
                            <td class="text-muted">{{ $log->reason ?? '—' }}</td>
                            <td class="font-mono text-xs text-muted">{{ $log->ip }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    @endif
</x-layouts.admin>
