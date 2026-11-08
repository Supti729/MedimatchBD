@php $s = $submission; $V = \App\Enums\VerificationStatus::class; $open = in_array($s->status, [$V::Submitted, $V::UnderReview], true); @endphp
<x-layouts.admin :title="'Verification #'.$s->id">
    <x-page-header :title="'Verification #'.$s->id.' · '.$s->kind" :back="route('admin.verifications.index')" back-label="Verification Center" />
    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <x-section title="Document" description="Private file. Not downloadable; opened through a short-lived, access-checked link in Part 2.">
            <div class="grid aspect-[4/3] place-items-center rounded-xl border-2 border-dashed border-line bg-canvas text-center text-muted">
                <div><x-icon name="file" class="mx-auto size-12 text-brand-600" /><p class="mt-2 font-semibold text-ink">{{ $s->document_type }}</p><p class="text-sm">No real document in the preview</p></div>
            </div>
        </x-section>
        <div class="space-y-6">
            <x-section title="Submission">
                <div class="mb-4"><x-status-badge :status="$s->status" /></div>
                <x-info-list :items="['Member' => $s->user->name, 'Check' => $s->kind, 'Related request' => $s->request ? $s->request->name.' · '.$s->request->quantity : '—', 'Submitted' => $s->submitted_at->format('d M Y, g:i a')]" />
            </x-section>
            @if ($open)
                <x-section title="Decision">
                    <form method="POST" action="{{ route('admin.verifications.decision', $s->id) }}" data-loading class="space-y-4">
                        @csrf @method('PATCH')
                        <fieldset class="space-y-2">
                            <legend class="label">Outcome</legend>
                            @foreach ([$V::Verified, $V::ReuploadRequired, $V::Rejected] as $o)
                                <label class="flex items-center gap-3 rounded-lg border border-line p-3 text-sm has-checked:border-brand-600 has-checked:bg-brand-50"><input type="radio" name="decision" value="{{ $o->value }}" required class="size-5 accent-brand-600"><x-status-badge :status="$o" /></label>
                            @endforeach
                        </fieldset>
                        <x-form.textarea name="note" label="Reviewer note" required rows="3" maxlength="500" hint="Shared with the member. Don't include medical details." />
                        <x-button type="submit" class="w-full" data-loading-text="Recording…">Record decision</x-button>
                    </form>
                </x-section>
            @endif
            <x-section title="Status history">
                <ol class="space-y-3 border-l-2 border-brand-100 pl-4 text-sm">
                    @foreach ($s->history as $h)<li><p class="font-semibold">{{ $h->label }}</p><p class="text-xs text-muted">{{ $h->at->format('d M Y, g:i a') }}</p></li>@endforeach
                </ol>
                @if ($s->reviewer_note)<p class="mt-4 rounded-lg bg-canvas px-3 py-2 text-sm"><strong>Note:</strong> {{ $s->reviewer_note }}</p>@endif
            </x-section>
        </div>
    </div>
</x-layouts.admin>
