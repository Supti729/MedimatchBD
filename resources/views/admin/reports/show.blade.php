@php $R = \App\Enums\ReportStatus::class; $open = in_array($report->status, [$R::Open, $R::UnderReview, $R::Escalated], true); @endphp
<x-layouts.admin :title="'Report #'.$report->id">
    <x-page-header :title="'Report #'.$report->id.': '.$report->reason" :back="route('admin.reports.index')" back-label="All reports" />
    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-section title="Details">
                <div class="mb-4"><x-status-badge :status="$report->status" /></div>
                <x-info-list :items="['Type' => $report->target_type, 'About' => $report->target_label, 'Reported member' => $report->reported_user->name, 'Reported by' => $report->reporter->name, 'Received' => $report->created_at->format('d M Y, g:i a')]" />
                <p class="mt-4 text-sm text-muted">{{ $report->details }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($report->listing)<x-button :href="route('admin.listings.show', $report->listing->id)" variant="neutral" size="sm" icon="pill">Open listing</x-button>@endif
                    <x-button :href="route('admin.users.show', $report->reported_user->id)" variant="neutral" size="sm" icon="user">Open member</x-button>
                </div>
            </x-section>
            <x-section title="Evidence"><x-empty-state title="No evidence files attached" icon="paperclip">Evidence uploads are stored privately and shown here to moderators only.</x-empty-state></x-section>
        </div>
        <div>
            @if ($open)
                <x-section title="Take action">
                    <form method="POST" action="{{ route('admin.reports.decision', $report->id) }}" data-loading class="space-y-4">
                        @csrf @method('PATCH')
                        <x-form.select name="decision" label="Decision" required placeholder="Choose an action" :options="['resolved' => 'Resolve — action taken', 'rejected' => 'Reject — report not valid', 'escalated' => 'Escalate to senior admin', 'suspend' => 'Resolve and suspend member', 'block_listing' => 'Resolve and block listing']" />
                        <x-form.textarea name="note" label="Moderator note" required rows="4" maxlength="1000" hint="Recorded in the audit log. The reporter receives a short status update." />
                        <x-button type="submit" class="w-full" data-loading-text="Recording…">Record decision</x-button>
                    </form>
                </x-section>
            @else
                <x-alert type="success" title="Closed">This report was {{ Str::lower($report->status->label()) }}. The decision is in the audit log.</x-alert>
            @endif
        </div>
    </div>
</x-layouts.admin>
