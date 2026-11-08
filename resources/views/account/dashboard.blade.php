<x-layouts.app title="Dashboard">
    <x-page-header :title="'Welcome back, '.strtok($me->name, ' ')" description="Here's what's happening with your listings, requests and conversations.">
        <x-slot:actions>
            <x-button :href="route('listings.create')" variant="secondary" icon="heart">Donate Medicine</x-button>
            <x-button :href="route('requests.create')" icon="plus">Request Medicine</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Active listings" :value="$stats['activeListings']" icon="pill" :href="route('listings.index', ['status' => 'active'])" />
        <x-stat-card label="Active requests" :value="$stats['activeRequests']" icon="inbox" tone="blue" :href="route('requests.index')" />
        <x-stat-card label="Pending verification" :value="$stats['pendingVerification']" icon="shield" tone="warm" :href="route('verification.index')" />
        <x-stat-card label="Reserved" :value="$stats['reserved']" icon="bookmark" tone="warm" :href="route('history')" />
        <x-stat-card label="Completed" :value="$stats['completed']" icon="check" tone="slate" :href="route('history')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-section title="Recommended matches" description="Listings that fit your open requests. Relevance is not medical advice.">
                <x-slot:actions><a href="{{ route('requests.index') }}" class="link text-sm">My requests</a></x-slot:actions>
                @forelse ($matches as $match)
                    <div class="flex flex-col gap-3 border-b border-line py-4 first:pt-0 last:border-0 last:pb-0 sm:flex-row sm:items-center">
                        <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="pill" class="size-6" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $match->listing->name }} <span class="font-normal text-muted">· {{ $match->listing->strength }} {{ $match->listing->form }}</span></p>
                            <p class="text-sm text-muted">{{ $match->listing->upazila }}, {{ $match->listing->district }} · for your request “{{ $match->request->name }}”</p>
                        </div>
                        <span class="text-sm font-semibold text-brand-700">{{ $match->relevance }}% relevance</span>
                        <x-button :href="route('medicines.show', $match->listing->id)" variant="neutral" size="sm">View</x-button>
                    </div>
                @empty
                    <x-empty-state title="No matches yet" icon="sparkles">Post a request and we'll show relevant listings here.</x-empty-state>
                @endforelse
                <p class="mt-4 text-xs text-muted">Relevance comes from rule-based matching (same medicine, strength and form; nearer areas rank higher). AI-assisted ranking is added in Part 3.</p>
            </x-section>

            <x-section title="Your requests">
                <x-slot:actions><x-button :href="route('requests.create')" size="sm" variant="ghost" icon="plus">New request</x-button></x-slot:actions>
                @if ($requests->isEmpty())
                    <x-empty-state title="No requests yet" icon="inbox">Looking for a medicine? Post a request and we'll match it with listings near you.</x-empty-state>
                @endif
                <ul class="divide-y divide-line">
                    @foreach ($requests as $req)
                        <li class="flex flex-wrap items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <a href="{{ route('requests.show', $req->id) }}" class="min-w-0 flex-1 font-semibold hover:text-brand-700">{{ $req->name }} <span class="font-normal text-muted">· {{ $req->quantity }}</span></a>
                            <x-status-badge :status="$req->status" />
                        </li>
                    @endforeach
                </ul>
            </x-section>
        </div>

        <div class="space-y-6">
            <x-section title="Quick actions">
                <div class="grid gap-2">
                    <a href="{{ route('medicines.index') }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-semibold hover:border-brand-600"><x-icon name="search" class="text-brand-600" />Find Medicine</a>
                    <a href="{{ route('listings.create') }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-semibold hover:border-brand-600"><x-icon name="heart" class="text-brand-600" />Donate Medicine</a>
                    <a href="{{ route('requests.create') }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-semibold hover:border-brand-600"><x-icon name="inbox" class="text-brand-600" />Request Medicine</a>
                </div>
            </x-section>
            <x-section title="Recent activity" :padded="false">
                <div class="divide-y divide-line">
                    @forelse ($activity as $notification)<x-notification-item :notification="$notification" compact />@empty<p class="px-4 py-6 text-center text-sm text-muted">No activity yet.</p>@endforelse
                </div>
                <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-3 text-center text-sm font-semibold text-brand-700 hover:bg-canvas">All notifications</a>
            </x-section>
        </div>
    </div>
</x-layouts.app>
