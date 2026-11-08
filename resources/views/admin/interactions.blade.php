<x-layouts.admin title="Interactions">
    <x-page-header title="Transaction monitoring" description="Hand-overs between members. Medimatch BD does not process payments; this view monitors status only." />
    <div class="table-wrap">
        <table class="table">
            <caption class="sr-only">Interactions</caption>
            <thead><tr><th scope="col">#</th><th scope="col">Medicine</th><th scope="col">Donor</th><th scope="col">Receiver</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Updated</th></tr></thead>
            <tbody>
                @foreach ($items as $i)
                    @php [$donor, $receiver] = $i->direction === 'given' ? [$i->listing->owner, $i->other] : [$i->other, \App\Support\SampleData::currentUser()]; @endphp
                    <tr>
                        <td class="text-muted">{{ $i->id }}</td>
                        <td class="font-semibold">{{ $i->listing->name }}</td>
                        <td>{{ $donor->name }}</td>
                        <td>{{ $receiver->name }}</td>
                        <td><x-status-badge :status="$i->listing->type" size="xs" /></td>
                        <td><x-status-badge :status="$i->status" /></td>
                        <td class="whitespace-nowrap text-muted">{{ $i->updated_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
