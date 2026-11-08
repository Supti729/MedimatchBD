<x-layouts.admin title="Interactions">
    <x-page-header title="Transaction monitoring" description="Hand-overs between members. Medimatch BD does not process payments; this view monitors status only." />
    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.interactions.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => ! $status, 'bg-white ring-1 ring-line' => $status])>All</a>
        @foreach (\App\Enums\InteractionStatus::cases() as $s)<a href="{{ route('admin.interactions.index', ['status' => $s->value]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand-600 text-white' => $status === $s, 'bg-white ring-1 ring-line' => $status !== $s])>{{ $s->label() }}</a>@endforeach
    </nav>
    @if ($items->isEmpty())
        <x-empty-state title="No interactions" icon="history" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Interactions</caption>
                <thead><tr><th scope="col">#</th><th scope="col">Medicine</th><th scope="col">Donor</th><th scope="col">Receiver</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Updated</th></tr></thead>
                <tbody>
                    @foreach ($items as $i)
                        <tr>
                            <td class="text-muted">{{ $i->id }}</td>
                            <td><a href="{{ route('admin.listings.show', $i->listing) }}" class="font-semibold hover:text-brand-700">{{ $i->listing->name }}</a></td>
                            <td><a href="{{ route('admin.users.show', $i->donor) }}" class="hover:text-brand-700">{{ $i->donor->name }}</a></td>
                            <td><a href="{{ route('admin.users.show', $i->requester) }}" class="hover:text-brand-700">{{ $i->requester->name }}</a></td>
                            <td><x-status-badge :status="$i->listing->type" size="xs" /></td>
                            <td><x-status-badge :status="$i->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $i->updated_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    @endif
</x-layouts.admin>
