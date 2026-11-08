<x-layouts.public title="About">
    <x-page-hero title="About Medimatch BD" eyebrow="Our project">A Software Engineering capstone project exploring how technology can make it easier, and safer, to find and share eligible medicine in Bangladesh.</x-page-hero>
    <x-prose>
        <h2>Why we are building it</h2>
        <p>Unused medicine often sits in homes until it expires, while someone nearby struggles to find or afford the same medicine. Medimatch BD connects these two sides through location-based search, private communication and controlled verification, with privacy and safety as the starting point rather than an afterthought.</p>
        <h2>What makes it different</h2>
        <ul>
            <li><strong>Not a marketplace.</strong> The core experience is donation and stays free for people who need help.</li>
            <li><strong>Privacy first.</strong> Members only ever see a name, photo, joined date and a trust indicator.</li>
            <li><strong>Configurable eligibility rules.</strong> A verified medicine catalogue decides what may be listed and what needs review.</li>
            <li><strong>AI that assists, never decides.</strong> Semantic matching ranks relevant listings; it never diagnoses, prescribes or approves documents.</li>
        </ul>
        <h2>Academic prototype</h2>
        <p>This is a university prototype, not an authorised medicine service. Redistributing, donating, verifying or delivering medicine in the real world must follow applicable Bangladesh regulations and would need proper authorisation and partners.</p>
        <h2>The team</h2>
    </x-prose>
    <div class="mx-auto -mt-6 grid max-w-4xl gap-4 px-4 sm:grid-cols-3">
        @foreach ([['Supti Saha', 'Team Leader', 'Requirements, UI/UX, frontend, coordination, documentation and presentation.'], ['Atiar Rahman Rahat', 'Backend & Database', 'Laravel, MySQL, authentication, listings, requests, search and interaction workflows.'], ['Emamul Muttakin', 'AI & Supporting Modules', 'Semantic matching, evaluation, verification and admin modules, security testing and deployment.']] as [$name, $role, $text])
            <div class="card p-6">
                <x-avatar :name="$name" size="lg" />
                <p class="mt-4 font-bold">{{ $name }}</p>
                <p class="text-sm font-semibold text-brand-700">{{ $role }}</p>
                <p class="mt-2 text-sm text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</x-layouts.public>
