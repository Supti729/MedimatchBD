<x-layouts.app title="Verification Center">
    <x-page-header title="Verification Center" description="Submit documents for identity or prescription checks. Documents are stored privately and seen only by authorised reviewers." />
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-section title="Your submissions" :padded="false">
                @forelse ($submissions as $s)
                    <div class="border-b border-line p-5 last:border-0">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold">{{ $s->kind }}@if ($s->request) <span class="font-normal text-muted">for request “{{ $s->request->name }}”</span>@endif</p>
                                <p class="text-sm text-muted">{{ $s->document_type }} · submitted {{ $s->submitted_at->format('d M Y') }}</p>
                            </div>
                            <x-status-badge :status="$s->status" />
                        </div>
                        @if ($s->reviewer_note)<p class="mt-3 rounded-lg bg-canvas px-3 py-2 text-sm"><strong>Reviewer note:</strong> {{ $s->reviewer_note }}</p>@endif
                        <ol class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-muted" aria-label="Status history">
                            @foreach ($s->history as $h)<li class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-brand-600"></span>{{ $h->label }} · {{ $h->at->format('d M, g:i a') }}</li>@endforeach
                        </ol>
                    </div>
                @empty
                    <div class="p-5"><x-empty-state title="No submissions yet" icon="shield" /></div>
                @endforelse
            </x-section>

            <x-section title="Submit a document">
                <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" data-loading class="space-y-5">
                    @csrf
                    <x-form.select name="kind" label="Type of check" required placeholder="Select type" :options="['identity' => 'Identity check', 'prescription' => 'Prescription check for a request']" />
                    <x-form.select name="request_id" label="Related request" placeholder="Not related to a request" :options="$requests->mapWithKeys(fn ($r) => [$r->id => $r->name.' · '.$r->quantity])->all()" hint="Required for prescription checks." />
                    <div>
                        <label for="document" class="label">Document <span class="text-red-700" aria-hidden="true">*</span></label>
                        <input id="document" name="document" type="file" required accept="image/jpeg,image/png,application/pdf" class="field file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand-700">
                        <p class="hint">JPG, PNG or PDF, up to 5 MB. Cover any information not needed for the check.</p>
                    </div>
                    <x-form.checkbox required name="consent" label="I consent to an authorised reviewer viewing this document for this check only." hint="The document is never shared with donors or other members." />
                    <x-button type="submit" icon="upload" data-loading-text="Uploading…">Submit for review</x-button>
                </form>
            </x-section>
        </div>
        <aside class="space-y-4">
            <div class="card p-5">
                <p class="font-bold">How verification works</p>
                <ol class="mt-3 space-y-3 text-sm text-muted">
                    <li class="flex gap-2"><span class="font-bold text-brand-700">1</span>You upload a document privately.</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-700">2</span>An authorised reviewer checks it. AI never approves documents.</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-700">3</span>You get a decision: verified, rejected, or re-upload required.</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-700">4</span>The donor only sees “verified”, never the document.</li>
                </ol>
            </div>
            <x-alert type="warning" title="Academic prototype">Please don't upload real prescriptions or ID documents to this prototype. Use sample images only.</x-alert>
        </aside>
    </div>
</x-layouts.app>
