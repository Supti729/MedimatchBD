<x-layouts.admin title="Dashboard">
    <x-page-header title="Admin dashboard" description="Platform overview. Figures are counted from records — sample records in this preview." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        @foreach ($stats as $label => [$value, $icon])
            <x-stat-card :label="$label" :value="$value" :icon="$icon" :tone="in_array($label, ['Pending verification', 'Open reports']) ? 'warm' : 'brand'" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-section title="Monthly registrations" description="Members by month joined (sample data)"><x-column-chart :items="$registrations" /></x-section>
        <x-section title="Donation vs Affordable Access" description="All listings by type"><x-bar-chart :items="$byType" /></x-section>
        <x-section title="Listings by district"><x-bar-chart :items="$listingsByDistrict" /></x-section>
        <x-section title="Requests by district"><x-bar-chart :items="$requestsByDistrict" tone="bg-blue-600" /></x-section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-section title="Listings awaiting review" :padded="false">
            <ul class="divide-y divide-line">
                @forelse ($queue['listings'] as $l)
                    <li><a href="{{ route('admin.listings.show', $l->id) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $l->name }}</span><span class="block text-muted">{{ $l->owner->name }} · {{ $l->district }}</span></span><x-icon name="chevron-right" class="size-4 text-muted" /></a></li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">Nothing waiting.</li>
                @endforelse
            </ul>
        </x-section>
        <x-section title="Verification queue" :padded="false">
            <ul class="divide-y divide-line">
                @foreach ($queue['verifications'] as $v)
                    <li><a href="{{ route('admin.verifications.show', $v->id) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $v->kind }}</span><span class="block text-muted">{{ $v->user->name }} · {{ $v->submitted_at->diffForHumans() }}</span></span><x-status-badge :status="$v->status" size="xs" /></a></li>
                @endforeach
            </ul>
        </x-section>
        <x-section title="Reports needing action" :padded="false">
            <ul class="divide-y divide-line">
                @foreach ($queue['reports'] as $r)
                    <li><a href="{{ route('admin.reports.show', $r->id) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas"><span><span class="font-semibold">{{ $r->reason }}</span><span class="block text-muted">{{ $r->target_type }} · {{ $r->target_label }}</span></span><x-status-badge :status="$r->status" size="xs" /></a></li>
                @endforeach
            </ul>
        </x-section>
    </div>

    <x-section title="Recent admin activity" class="mt-6" :padded="false">
        <x-slot:actions><a href="{{ route('admin.audit-logs.index') }}" class="link text-sm">Full audit log</a></x-slot:actions>
        <ul class="divide-y divide-line text-sm">
            @foreach ($audit as $log)
                <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3"><code class="rounded bg-canvas px-2 py-0.5 text-xs">{{ $log->action }}</code><span class="flex-1">{{ $log->target }} <span class="text-muted">by {{ $log->actor->name }}</span></span><span class="text-xs text-muted">{{ $log->created_at->diffForHumans() }}</span></li>
            @endforeach
        </ul>
    </x-section>
</x-layouts.admin>
