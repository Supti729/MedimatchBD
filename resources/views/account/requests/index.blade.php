<x-layouts.app title="My Requests">
    <x-page-header title="My requests" description="Medicine you are looking for. We'll notify you when a relevant listing appears.">
        <x-slot:actions><x-button :href="route('requests.create')" icon="plus">New request</x-button></x-slot:actions>
    </x-page-header>
    @if ($requests->isEmpty())
        <x-empty-state title="You haven't requested any medicine" icon="inbox">Search first — if nothing fits, post a request.<x-slot:actions><x-button :href="route('requests.create')" icon="plus">New request</x-button></x-slot:actions></x-empty-state>
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Your medicine requests</caption>
                <thead><tr><th scope="col">Medicine</th><th scope="col">Quantity</th><th scope="col">Area</th><th scope="col">Status</th><th scope="col">Matches</th><th scope="col">Created</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td><a href="{{ route('requests.show', $req->id) }}" class="font-semibold hover:text-brand-700">{{ $req->name }}</a><span class="block text-xs text-muted">{{ $req->generic }} · {{ $req->strength }} {{ $req->form }}</span></td>
                            <td class="whitespace-nowrap">{{ $req->quantity }}</td>
                            <td class="whitespace-nowrap">{{ $req->upazila }}, {{ $req->district }}</td>
                            <td><x-status-badge :status="$req->status" /></td>
                            <td>{{ $req->matches_count }}</td>
                            <td class="whitespace-nowrap text-muted">{{ $req->created_at->format('d M Y') }}</td>
                            <td class="text-right"><x-button :href="route('requests.show', $req->id)" variant="neutral" size="sm">Open</x-button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
