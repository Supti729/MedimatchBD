<x-layouts.app title="Interaction History">
    <x-page-header title="Interaction history" description="Every hand-over you've given or received, with its current status." />
    @if ($interactions->isEmpty())
        <x-empty-state title="No interactions yet" icon="history" />
    @else
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Your interactions</caption>
                <thead><tr><th scope="col">Medicine</th><th scope="col">Direction</th><th scope="col">Other member</th><th scope="col">Status</th><th scope="col">Updated</th></tr></thead>
                <tbody>
                    @foreach ($interactions as $i)
                        <tr>
                            <td><span class="font-semibold">{{ $i->listing->name }}</span><span class="block text-xs text-muted">{{ $i->listing->strength }} {{ $i->listing->form }} · {{ $i->listing->quantity }}</span></td>
                            <td><span class="inline-flex items-center gap-1.5"><x-icon :name="$i->direction === 'given' ? 'heart' : 'inbox'" class="size-4 text-brand-600" />{{ $i->direction === 'given' ? 'Given' : 'Received' }}</span></td>
                            <td><a href="{{ route('members.show', $i->other->id) }}" class="hover:text-brand-700">{{ $i->other->name }}</a></td>
                            <td><x-status-badge :status="$i->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $i->updated_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
