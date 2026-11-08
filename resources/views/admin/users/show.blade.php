@php $A = \App\Enums\AccountStatus::class; @endphp
<x-layouts.admin :title="$user->name">
    <x-page-header :title="$user->name" :back="route('admin.users.index')" back-label="All users">
        <x-slot:actions>
            @if ($user->status === $A::Suspended)
                <x-button icon="refresh" data-dialog-open="restore-user">Restore account</x-button>
            @else
                @if ($user->status === $A::Pending)<x-button variant="secondary" icon="check" data-dialog-open="activate-user">Activate</x-button>@endif
                <x-button variant="danger" icon="x" data-dialog-open="suspend-user">Suspend account</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-6 xl:grid-cols-[22rem_1fr]">
        <x-section title="Account">
            <div class="mb-4 flex items-center gap-3"><x-avatar :name="$user->name" size="lg" /><div><x-status-badge :status="$user->status" /><p class="mt-1 text-sm capitalize text-muted">{{ $user->role }}</p></div></div>
            <x-info-list :items="['Email' => $user->email, 'Phone' => $user->phone, 'Area' => $user->upazila.', '.$user->district.', '.$user->division, 'Joined' => $user->joined_at->format('d M Y'), 'Last active' => $user->last_active_at->diffForHumans(), 'Identity' => $user->is_verified ? 'Verified' : 'Not verified']" />
            <p class="mt-3 text-xs text-muted">Contact details are visible to administrators only. Viewing this page is recorded in the audit log.</p>
        </x-section>
        <div class="space-y-6">
            <x-section title="Listings ({{ $listings->count() }})" :padded="false">
                <ul class="divide-y divide-line text-sm">
                    @forelse ($listings as $l)<li class="flex items-center justify-between gap-3 px-5 py-3"><a href="{{ route('admin.listings.show', $l->id) }}" class="font-semibold hover:text-brand-700">{{ $l->name }}</a><x-status-badge :status="$l->status" size="xs" /></li>@empty<li class="px-5 py-4 text-muted">No listings.</li>@endforelse
                </ul>
            </x-section>
            <x-section title="Requests ({{ $requests->count() }})" :padded="false">
                <ul class="divide-y divide-line text-sm">
                    @forelse ($requests as $r)<li class="flex items-center justify-between gap-3 px-5 py-3"><span class="font-semibold">{{ $r->name }}</span><x-status-badge :status="$r->status" size="xs" /></li>@empty<li class="px-5 py-4 text-muted">No requests.</li>@endforelse
                </ul>
            </x-section>
            <x-section title="Reports about this member" :padded="false">
                <ul class="divide-y divide-line text-sm">
                    @forelse ($reports as $r)<li class="flex items-center justify-between gap-3 px-5 py-3"><a href="{{ route('admin.reports.show', $r->id) }}" class="font-semibold hover:text-brand-700">{{ $r->reason }}</a><x-status-badge :status="$r->status" size="xs" /></li>@empty<li class="px-5 py-4 text-muted">No reports.</li>@endforelse
                </ul>
            </x-section>
        </div>
    </div>
    @foreach (['suspend' => ['Suspend '.$user->name.'?', 'suspended', 'Suspend', 'danger', 'The member is logged out and cannot sign in. Their listings are hidden from search.'], 'restore' => ['Restore '.$user->name.'?', 'active', 'Restore', 'primary', 'The member can sign in again.'], 'activate' => ['Activate '.$user->name.'?', 'active', 'Activate', 'primary', 'Marks the account as active.']] as $key => [$title, $value, $confirm, $variant, $text])
        <x-confirm-dialog :id="$key.'-user'" :title="$title" :action="route('admin.users.status', $user->id)" method="PATCH" :confirm="$confirm" :variant="$variant" reason>{{ $text }}<input type="hidden" name="status" value="{{ $value }}"></x-confirm-dialog>
    @endforeach
</x-layouts.admin>
