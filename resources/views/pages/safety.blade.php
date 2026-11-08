<x-layouts.public title="Safety Guidelines">
    <x-page-hero title="Safety guidelines" eyebrow="Trust & safety">Medicine is not an ordinary item. These rules keep members safe and keep the platform within the law.</x-page-hero>
    <div class="mx-auto max-w-5xl px-4 py-12">
        <x-alert type="warning" title="Medimatch BD does not give medical advice">The platform does not diagnose, prescribe, recommend treatment or judge whether a medicine is suitable for anyone. Always ask a registered doctor or pharmacist before taking a medicine.</x-alert>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            @php
                $groups = [
                    ['pill', 'What can be listed', ['Sealed, unopened strips, bottles or packs', 'Within the expiry date, with the date clearly visible', 'Medicine that matches an entry in the verified catalogue', 'Stored as the label instructs (e.g. away from heat and sunlight)']],
                    ['x', 'What cannot be listed', ['Expired, damaged, opened or unlabelled medicine', 'Medicine on the restricted list (for example controlled drugs or items that need cold storage)', 'Free samples marked "not for sale"', 'Anything offered for private resale or profit']],
                    ['shield', 'Verification', ['Some medicines need a prescription check before a request can be fulfilled', 'Documents are seen only by authorised reviewers, never by donors', 'Reviewer decisions are recorded, and AI never approves a prescription']],
                    ['users', 'Meeting safely', ['Meet in a busy public place during the day', 'Bring someone with you if you can', 'Check the seal, name, strength and expiry before you accept', 'Never share your home address, phone number or NID in chat']],
                ];
            @endphp
            @foreach ($groups as [$icon, $title, $items])
                <section class="card p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold"><x-icon :name="$icon" class="text-brand-600" />{{ $title }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-muted">
                        @foreach ($items as $item)<li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-brand-600" />{{ $item }}</li>@endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <section class="mt-10 rounded-2xl bg-brand-900 p-8 text-white">
            <h2 class="text-xl font-bold">See something unsafe?</h2>
            <p class="mt-2 text-brand-100">Use <strong>Report</strong> on any listing, member or message. Moderators review every report and record their decision.</p>
            <x-button :href="route('contact')" variant="light" class="mt-5">Contact the team</x-button>
        </section>
    </div>
</x-layouts.public>
