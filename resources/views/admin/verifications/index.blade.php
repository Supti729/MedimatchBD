<x-layouts.admin title="Verification Center">
    <x-page-header title="Verification Center" description="Identity and prescription checks. Restricted to authorised reviewers; every document view is logged." />
    <x-alert type="warning" class="mb-5">AI never approves documents. Decisions are made by an authorised reviewer and recorded with a reason.</x-alert>
    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.verifications.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => ! $status, 'bg-white ring-1 ring-line' => $status])>All</a>
        @foreach (\App\Enums\VerificationStatus::cases() as $s)
            @continue($s === \App\Enums\VerificationStatus::NotSubmitted)
            <a href="{{ route('admin.verifications.index', ['status' => $s->value]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => $status === $s, 'bg-white ring-1 ring-line' => $status !== $s])>{{ $s->label() }}</a>
        @endforeach
    </nav>
    @if ($submissions->isEmpty())
        <x-empty-state title="No submissions" icon="shield" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Verification submissions</caption>
                <thead><tr><th scope="col">#</th><th scope="col">Member</th><th scope="col">Check</th><th scope="col">Document</th><th scope="col">Status</th><th scope="col">Submitted</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($submissions as $s)
                        <tr>
                            <td class="text-muted">{{ $s->id }}</td>
                            <td>{{ $s->user->name }}</td>
                            <td>{{ $s->kind_label }}@if ($s->request)<span class="block text-xs text-muted">Request: {{ $s->request->name }}</span>@endif</td>
                            <td class="text-muted">{{ $s->document_type }}</td>
                            <td><x-status-badge :status="$s->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $s->created_at->format('d M Y') }}</td>
                            <td class="text-right">@if ($s->user_id !== auth()->id())<x-button :href="route('admin.verifications.show', $s)" variant="neutral" size="sm">{{ $s->status->isPending() ? 'Review' : 'View' }}</x-button>@else<span class="text-xs text-muted">Your own</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $submissions->links() }}
    @endif
</x-layouts.admin>
