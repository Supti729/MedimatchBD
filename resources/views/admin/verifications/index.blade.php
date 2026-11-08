<x-layouts.admin title="Verification Center">
    <x-page-header title="Verification Center" description="Identity and prescription checks. Restricted to authorised reviewers; every document view is logged." />
    <x-alert type="warning" class="mb-5">AI never approves documents. Decisions are made by an authorised reviewer and recorded with a reason.</x-alert>
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Verification submissions</caption>
            <thead><tr><th scope="col">#</th><th scope="col">Member</th><th scope="col">Check</th><th scope="col">Document</th><th scope="col">Status</th><th scope="col">Submitted</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($submissions as $s)
                    <tr>
                        <td class="text-muted">{{ $s->id }}</td>
                        <td>{{ $s->user->name }}</td>
                        <td>{{ $s->kind }}@if ($s->request)<span class="block text-xs text-muted">Request: {{ $s->request->name }}</span>@endif</td>
                        <td class="text-muted">{{ $s->document_type }}</td>
                        <td><x-status-badge :status="$s->status" /></td>
                        <td class="whitespace-nowrap text-muted">{{ $s->submitted_at->format('d M Y') }}</td>
                        <td class="text-right"><x-button :href="route('admin.verifications.show', $s->id)" variant="neutral" size="sm">Review</x-button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
