{{-- Public profile: minimum information only. No phone, email, address or documents. --}}
<x-layouts.public :title="$member->name">
    <div class="mx-auto max-w-5xl px-4 py-10">
        <section class="card flex flex-col items-center gap-5 p-8 text-center sm:flex-row sm:text-left">
            <x-avatar :user="$member" size="xl" />
            <div class="flex-1">
                <h1 class="text-2xl font-extrabold text-brand-900">{{ $member->name }}</h1>
                <p class="text-muted">Member since {{ $member->joined_at->format('F Y') }}</p>
                <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @if ($member->is_verified)<span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-800"><x-icon name="shield" class="size-4" />Identity verified</span>@endif
                    @if ($member->privacySetting('show_contributions'))<span class="rounded-full bg-canvas px-2.5 py-1 text-xs font-semibold text-muted ring-1 ring-line">{{ $member->completed_count }} completed {{ Str::plural('contribution', $member->completed_count) }}</span>@endif
                </div>
            </div>
            @auth
                @if (auth()->id() !== $member->id)<button type="button" data-dialog-open="report-member" class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-700 hover:underline"><x-icon name="flag" class="size-4" />Report</button>@endif
            @endauth
        </section>
        <p class="mt-3 flex items-center gap-2 text-xs text-muted"><x-icon name="lock" class="size-4" />Contact details and exact location are private and never shown on profiles.</p>

        <h2 class="mt-10 text-xl font-bold">Current listings</h2>
        @if ($listings->isEmpty())
            <x-empty-state title="No public listings right now" icon="pill" class="mt-4" />
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($listings as $listing)<x-medicine-card :listing="$listing" />@endforeach</div>
        @endif
    </div>
    @auth
        <x-confirm-dialog id="report-member" title="Report {{ $member->name }}" :action="route('reports.store')" confirm="Send report" variant="danger">
            Reports are private and reviewed by moderators.
            <input type="hidden" name="user_id" value="{{ $member->id }}">
            <label for="member-report-reason" class="label mt-4">Reason</label>
            <select id="member-report-reason" name="category" required class="field"><option value="">Choose a reason</option>@foreach (\App\Models\Report::CATEGORIES as $category)<option>{{ $category }}</option>@endforeach</select>
            <label for="member-report-details" class="label mt-3">What happened?</label>
            <textarea id="member-report-details" name="details" rows="3" maxlength="1000" class="field"></textarea>
        </x-confirm-dialog>
    @endauth
</x-layouts.public>
