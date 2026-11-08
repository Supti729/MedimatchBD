<x-layouts.app title="Profile">
    <x-page-header title="My profile" description="Your private profile. Only you and administrators can see the details on this page.">
        <x-slot:actions>
            <x-button :href="route('members.show', $me->id)" variant="neutral" icon="eye">View public profile</x-button>
            <x-button :href="route('profile.edit')" icon="edit">Edit profile</x-button>
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        <x-section>
            <div class="text-center">
                <x-avatar :name="$me->name" size="xl" class="mx-auto" />
                <p class="mt-4 text-xl font-bold">{{ $me->name }}</p>
                <p class="text-sm text-muted">Member since {{ $me->joined_at->format('F Y') }}</p>
                <div class="mt-3 flex justify-center"><x-status-badge :status="$me->status" /></div>
                @if ($me->is_verified)<p class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-700"><x-icon name="shield" class="size-4" />Identity verified</p>@endif
            </div>
            <dl class="mt-6 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-canvas p-3"><dt class="text-xs text-muted">Listings</dt><dd class="text-xl font-bold">{{ $listingCount }}</dd></div>
                <div class="rounded-lg bg-canvas p-3"><dt class="text-xs text-muted">Requests</dt><dd class="text-xl font-bold">{{ $requestCount }}</dd></div>
                <div class="rounded-lg bg-canvas p-3"><dt class="text-xs text-muted">Completed</dt><dd class="text-xl font-bold">{{ $me->completed_count }}</dd></div>
            </dl>
        </x-section>
        <div class="space-y-6">
            <x-section title="Personal details" description="Private: never shown to other members.">
                <x-info-list :items="['Full name' => $me->name, 'Email' => $me->email, 'Phone' => $me->phone, 'Division' => $me->division, 'District' => $me->district, 'Upazila / Thana' => $me->upazila]" />
            </x-section>
            <x-section title="What other members see">
                <div class="flex items-center gap-4 rounded-xl bg-canvas p-4">
                    <x-avatar :name="$me->name" size="lg" />
                    <div><p class="font-bold">{{ $me->name }}</p><p class="text-sm text-muted">Joined {{ $me->joined_at->format('F Y') }} · {{ $me->completed_count }} completed contributions</p></div>
                </div>
                <p class="mt-3 text-sm text-muted">Your phone, email and exact address are never shown. Change what appears in <a href="{{ route('settings.privacy') }}" class="link">Privacy Settings</a>.</p>
            </x-section>
        </div>
    </div>
</x-layouts.app>
