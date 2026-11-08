@php $s = $submission; $V = \App\Enums\VerificationStatus::class; @endphp
<x-layouts.admin :title="'Verification #'.$s->id">
    <x-page-header :title="'Verification #'.$s->id.' · '.$s->kind_label" :back="route('admin.verifications.index')" back-label="Verification Center" />
    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <x-section title="Document" description="Private file streamed through an access-checked link. It is never cached or publicly reachable.">
            @if (str_starts_with($s->mime_type, 'image/'))
                <img src="{{ route('admin.verifications.document', $s) }}" alt="Submitted document" class="mx-auto max-h-[70vh] rounded-lg border border-line">
            @else
                <iframe src="{{ route('admin.verifications.document', $s) }}" title="Submitted document" class="h-[70vh] w-full rounded-lg border border-line"></iframe>
            @endif
            <p class="mt-3 text-xs text-muted">{{ $s->original_name }} · {{ number_format($s->size_bytes / 1024) }} KB</p>
        </x-section>
        <div class="space-y-6">
            <x-section title="Submission">
                <div class="mb-4"><x-status-badge :status="$s->status" /></div>
                <x-info-list :items="['Member' => $s->user->name, 'Check' => $s->kind_label, 'Related request' => $s->request ? $s->request->name.' · '.$s->request->strength.' · '.$s->request->quantity : '—', 'Submitted' => $s->created_at->format('d M Y, g:i a')]" />
            </x-section>
            @can('decide', $s)
                <x-section title="Decision">
                    <form method="POST" action="{{ route('admin.verifications.decision', $s) }}" data-loading class="space-y-4">
                        @csrf @method('PATCH')
                        <fieldset class="space-y-2">
                            <legend class="label">Outcome</legend>
                            @foreach ([$V::Verified, $V::ReuploadRequired, $V::Rejected] as $o)
                                <label class="flex items-center gap-3 rounded-lg border border-line p-3 text-sm has-checked:border-brand-600 has-checked:bg-brand-50"><input type="radio" name="decision" value="{{ $o->value }}" required @checked(old('decision') === $o->value) class="size-5 accent-brand-600"><x-status-badge :status="$o" /></label>
                            @endforeach
                        </fieldset>
                        <x-form.textarea name="note" label="Reviewer note" required rows="3" maxlength="500" hint="Shared with the member. Don't include medical details." />
                        <x-button type="submit" class="w-full" data-loading-text="Recording…">Record decision</x-button>
                    </form>
                </x-section>
            @endcan
            <x-section title="Status history">
                <ol class="space-y-3 border-l-2 border-brand-100 pl-4 text-sm">
                    @foreach ($s->reviews as $h)<li><p class="font-semibold">{{ $h->to_status->label() }}</p><p class="text-xs text-muted">{{ $h->created_at->format('d M Y, g:i a') }}{{ $h->reviewer ? ' · '.$h->reviewer->name : '' }}</p>@if ($h->note)<p class="mt-1 text-muted">{{ $h->note }}</p>@endif</li>@endforeach
                </ol>
            </x-section>
        </div>
    </div>
</x-layouts.admin>
