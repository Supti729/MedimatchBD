<x-layouts.admin title="Requests">
    <x-page-header title="Request management" description="All medicine requests across the platform. Notes are visible only when needed for moderation." />
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Requests</caption>
            <thead><tr><th scope="col">#</th><th scope="col">Medicine</th><th scope="col">Member</th><th scope="col">District</th><th scope="col">Preference</th><th scope="col">Matches</th><th scope="col">Status</th><th scope="col">Created</th></tr></thead>
            <tbody>
                @foreach ($requests as $r)
                    <tr>
                        <td class="text-muted">{{ $r->id }}</td>
                        <td><span class="font-semibold">{{ $r->name }}</span><span class="block text-xs text-muted">{{ $r->strength }} {{ $r->form }} · {{ $r->quantity }}</span></td>
                        <td><a href="{{ route('admin.users.show', $r->owner->id) }}" class="hover:text-brand-700">{{ $r->owner->name }}</a></td>
                        <td>{{ $r->district }}</td>
                        <td class="text-xs">{{ \App\Support\SampleData::preferenceLabel($r->preference) }}</td>
                        <td>{{ $r->matches->count() }}</td>
                        <td><x-status-badge :status="$r->status" /></td>
                        <td class="whitespace-nowrap text-muted">{{ $r->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
