<x-layouts.public title="Contact">
    <x-page-hero title="Contact us" eyebrow="Get in touch">Questions about the project, a partnership idea or a safety concern? Send us a message.</x-page-hero>
    <div class="mx-auto grid max-w-5xl gap-8 px-4 py-12 lg:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('contact.send') }}" data-loading class="card space-y-5 p-6 sm:p-8">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Your name" required autocomplete="name" maxlength="100" />
                <x-form.input name="email" type="email" label="Email address" required autocomplete="email" maxlength="150" />
            </div>
            <x-form.select name="topic" label="Topic" required placeholder="Choose a topic" :options="['general' => 'General question', 'safety' => 'Safety concern', 'partnership' => 'Partnership or sponsorship', 'privacy' => 'Privacy or my data', 'technical' => 'Technical problem']" />
            <x-form.textarea name="message" label="Message" required rows="6" maxlength="2000" hint="Please don't include prescriptions, ID numbers or other sensitive details." />
            <x-button type="submit" icon="send" data-loading-text="Sending…">Send message</x-button>
        </form>
        <aside class="space-y-4">
            <div class="card p-5"><x-icon name="flag" class="text-brand-600" /><p class="mt-3 font-bold">Reporting a listing?</p><p class="mt-1 text-sm text-muted">Use the Report button on the listing itself, so moderators see exactly what you mean.</p></div>
            <div class="card p-5"><x-icon name="alert" class="text-warm-700" /><p class="mt-3 font-bold">Medical emergency?</p><p class="mt-1 text-sm text-muted">This platform cannot help in an emergency. Call the national emergency number <strong class="text-ink">999</strong> or go to the nearest hospital.</p></div>
        </aside>
    </div>
</x-layouts.public>
