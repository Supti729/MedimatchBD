<x-layouts.admin title="Dashboard">
    @if (! $isAdmin)
        <x-page-header title="Reviewer dashboard" description="Your verification queue. Documents are visible only to authorised reviewers and every view is logged." />
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($stats as $label => [$value, $icon])<x-stat-card :label="$label" :value="$value" :icon="$icon" tone="warm" :href="route('admin.verifications.index')" />@endforeach
        </div>
        <x-section title="Oldest waiting first" class="mt-6" :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($queue['verifications'] as $v)
                    <li><a href="{{ route('admin.verifications.show', $v) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $v->kind_label }}</span><span class="block text-muted">{{ $v->user->name }} · {{ $v->created_at->diffForHumans() }}</span></span><x-status-badge :status="$v->status" size="xs" /></a></li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">Nothing waiting. 🎉</li>
                @endforelse
            </ul>
        </x-section>
    @else
        <x-page-header title="Admin dashboard" description="Platform overview. Every figure is counted live from the database." />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($stats as $label => [$value, $icon])
                <x-stat-card :label="$label" :value="$value" :icon="$icon" :tone="in_array($label, ['Pending verification', 'Open reports']) && $value ? 'warm' : 'brand'" />
            @endforeach
        </div>
        <p class="mt-2 text-xs text-muted">Active users = members active in the last 30 days.</p>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-section title="New members" description="Registrations per month, last 6 months"><x-column-chart :items="$registrations" /></x-section>
            <x-section title="Completed hand-overs" description="Per month, last 6 months"><x-column-chart :items="$completions" /></x-section>
            <x-section title="Listings by district" description="Top 6 districts">
                @if ($listingsByDistrict)<x-bar-chart :items="$listingsByDistrict" />@else<p class="text-sm text-muted">No listings yet.</p>@endif
            </x-section>
            <x-section title="Requests by district" description="Top 6 districts">
                @if ($requestsByDistrict)<x-bar-chart :items="$requestsByDistrict" tone="bg-blue-600" />@else<p class="text-sm text-muted">No requests yet.</p>@endif
            </x-section>
            <x-section title="Donation vs Affordable Access" description="All listings by type" class="xl:col-span-2"><x-bar-chart :items="$byType" /></x-section>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <x-section title="Listings awaiting review" :padded="false">
                <ul class="divide-y divide-line">
                    @forelse ($queue['listings'] as $l)
                        <li><a href="{{ route('admin.listings.show', $l) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $l->name }}</span><span class="block text-muted">{{ $l->owner->name }} · {{ $l->district }}</span></span><x-icon name="chevron-right" class="size-4 text-muted" /></a></li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">Nothing waiting.</li>
                    @endforelse
                </ul>
            </x-section>
            <x-section title="Verification queue" :padded="false">
                <ul class="divide-y divide-line">
                    @forelse ($queue['verifications'] as $v)
                        <li><a href="{{ route('admin.verifications.show', $v) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $v->kind_label }}</span><span class="block text-muted">{{ $v->user->name }} · {{ $v->created_at->diffForHumans() }}</span></span><x-status-badge :status="$v->status" size="xs" /></a></li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">Nothing waiting.</li>
                    @endforelse
                </ul>
            </x-section>
            <x-section title="Reports needing action" :padded="false">
                <ul class="divide-y divide-line">
                    @forelse ($queue['reports'] as $r)
                        <li><a href="{{ route('admin.reports.show', $r) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $r->reason }}</span><span class="block text-muted">{{ $r->target_type }} · {{ $r->target_label }}</span></span><x-status-badge :status="$r->status" size="xs" /></a></li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No open reports.</li>
                    @endforelse
                </ul>
            </x-section>
        </div>

        <x-section title="Recent admin activity" class="mt-6" :padded="false">
            <x-slot:actions><a href="{{ route('admin.audit-logs.index') }}" class="link text-sm">Full audit log</a></x-slot:actions>
            <ul class="divide-y divide-line text-sm">
                @forelse ($audit as $log)
                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3"><code class="rounded bg-canvas px-2 py-0.5 text-xs">{{ $log->action }}</code><span class="flex-1">{{ $log->target }} <span class="text-muted">by {{ $log->actor?->name ?? 'system' }}</span></span><span class="text-xs text-muted">{{ $log->created_at->diffForHumans() }}</span></li>
                @empty
                    <li class="px-5 py-6 text-center text-muted">No admin activity yet.</li>
                @endforelse
            </ul>
        </x-section>
    @endif
</x-layouts.admin>
