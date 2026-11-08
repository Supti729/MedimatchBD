<x-layouts.admin :title="'Report #'.$report->id">
    <x-page-header :title="'Report #'.$report->id.': '.$report->category" :back="route('admin.reports.index')" back-label="All reports" />
    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-section title="Details">
                <div class="mb-4"><x-status-badge :status="$report->status" /></div>
                <x-info-list :items="['Type' => $report->target_type, 'About' => $report->target_label, 'Reported member' => $report->reportedUser?->name ?? '—', 'Reported by' => $report->reporter->name, 'Received' => $report->created_at->format('d M Y, g:i a')]" />
                <p class="mt-4 text-sm text-muted">{{ $report->details ?: 'No further details were given.' }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($report->listing)<x-button :href="route('admin.listings.show', $report->listing)" variant="neutral" size="sm" icon="pill">Open listing</x-button>@endif
                    @if ($report->reportedUser)<x-button :href="route('admin.users.show', $report->reportedUser)" variant="neutral" size="sm" icon="user">Open member</x-button>@endif
                </div>
            </x-section>
            @if ($messages->isNotEmpty())
                <x-section title="Reported conversation" description="Shown to moderators only because it was reported. Latest 30 messages.">
                    <ul class="space-y-2 text-sm">
                        @foreach ($messages as $m)<li class="rounded-lg bg-canvas px-3 py-2"><span class="font-semibold">{{ $m->sender->name }}</span> <span class="text-xs text-muted">{{ $m->created_at->format('d M, g:i a') }}</span><p>{{ $m->body }}</p></li>@endforeach
                    </ul>
                </x-section>
            @endif
        </div>
        <div>
            @if ($report->status->isOpen())
                <x-section title="Take action">
                    <form method="POST" action="{{ route('admin.reports.decision', $report) }}" data-loading class="space-y-4">
                        @csrf @method('PATCH')
                        <x-form.select name="decision" label="Decision" required placeholder="Choose an action" :options="array_filter(['resolved' => 'Resolve — action taken', 'rejected' => 'Reject — report not valid', 'escalated' => 'Escalate', 'suspend' => $report->reportedUser && ! $report->reportedUser->isStaff() ? 'Resolve and suspend member' : null, 'block_listing' => $report->listing ? 'Resolve and block listing' : null])" />
                        <x-form.textarea name="note" label="Moderator note" required rows="4" maxlength="1000" hint="Recorded in the audit log. The reporter receives a short status update." />
                        <x-button type="submit" class="w-full" data-loading-text="Recording…">Record decision</x-button>
                    </form>
                </x-section>
            @else
                <x-alert type="success" title="Closed">This report was {{ Str::lower($report->status->label()) }} by {{ $report->handler?->name ?? 'a moderator' }}{{ $report->resolved_at ? ' on '.$report->resolved_at->format('d M Y') : '' }}.@if ($report->resolution_note)<br>Note: {{ $report->resolution_note }}@endif</x-alert>
            @endif
        </div>
    </div>
</x-layouts.admin>
