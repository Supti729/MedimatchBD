<x-layouts.public title="Privacy Policy">
    <x-page-hero title="Privacy Policy" eyebrow="Legal">How Medimatch BD collects, uses and protects personal information. Draft for the academic prototype.</x-page-hero>
    <x-prose>
        <x-alert type="info">This draft describes how the prototype is designed. A real service would need a policy reviewed against current Bangladesh law before launch.</x-alert>
        <h2>1. Information we collect</h2>
        <ul>
            <li><strong>Account details:</strong> name, email address and/or phone number, password (stored only as a secure hash) and your general area (division, district, upazila).</li>
            <li><strong>Listings and requests:</strong> the medicine details you enter and photos of the packaging.</li>
            <li><strong>Messages:</strong> conversations you have with other members inside the platform.</li>
            <li><strong>Verification documents:</strong> only when a workflow requires it, for example a prescription check.</li>
        </ul>
        <h2>2. What other members can see</h2>
        <p>Other members see only your name, profile photo, joined date, a trust indicator and limited contribution counts. Your phone number, email, exact address, prescriptions and private documents are never shown to other members.</p>
        <h2>3. Sensitive documents</h2>
        <p>Verification documents are stored privately, are never given a public link, and can be opened only by authorised reviewers. Donors never receive a requester's prescription; they only see whether a check was passed. Every access by a reviewer is recorded.</p>
        <h2>4. How we use information</h2>
        <ul>
            <li>To run your account, show relevant listings near you and send notifications you have enabled.</li>
            <li>To keep the platform safe: moderation, fraud prevention and audit logs.</li>
            <li>To improve matching. Search text may be processed by our matching service, which does not make medical decisions.</li>
        </ul>
        <h2>5. What we never do</h2>
        <ul><li>Sell personal information or prescription data.</li><li>Share your contact details with sponsors or partners.</li><li>Let sponsors or partners bypass safety rules.</li></ul>
        <h2>6. Your choices</h2>
        <p>You can edit your profile, change privacy settings, choose which notifications you receive, and request deletion of your account from <a href="{{ route('settings.privacy') }}">Privacy Settings</a>.</p>
        <h2>7. Contact</h2>
        <p>Questions about privacy can be sent through the <a href="{{ route('contact') }}">contact page</a>.</p>
    </x-prose>
</x-layouts.public>
