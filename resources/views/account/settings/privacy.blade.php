<x-layouts.app title="Privacy Settings">
    <x-page-header title="Settings" />
    @include('account.settings._tabs')
    <form method="POST" action="{{ route('settings.privacy.update') }}" data-loading class="max-w-3xl space-y-6">
        @csrf @method('PATCH')
        <x-section title="Public profile" description="Your phone, email and exact address are never shown, whatever you choose here.">
            <div class="space-y-4">
                <x-form.checkbox name="show_photo" label="Show my profile photo" :checked="$me->privacySetting('show_photo')" />
                <x-form.checkbox name="show_contributions" label="Show my number of completed contributions" :checked="$me->privacySetting('show_contributions')" />
                <x-form.checkbox name="show_upazila" label="Show my upazila on listings" hint="If off, only your district is shown." :checked="$me->privacySetting('show_upazila')" />
            </div>
        </x-section>
        <x-section title="Messages">
            <x-form.select name="who_can_message" label="Who can message me about my listings" :options="['matched' => 'Members interested in my listings', 'none' => 'Only members who formally request a listing']" :value="$me->privacySetting('who_can_message')" />
        </x-section>
        <x-section title="Notifications" description="In-platform notifications are always on. Email and SMS are used only if a provider is configured.">
            <div class="space-y-4">
                <x-form.checkbox name="notify_email" label="Email me about new matches and messages" :checked="$me->privacySetting('notify_email')" hint="Saved for when an email provider is configured. Not sent in this prototype." />
                <label class="flex items-start gap-3 opacity-60"><input type="checkbox" disabled class="mt-0.5 size-5"><span class="text-sm"><span class="font-medium">Text me about reservations</span><span class="block text-muted">Not available: no SMS provider is configured.</span></span></label>
            </div>
        </x-section>
        <div class="flex justify-end"><x-button type="submit" data-loading-text="Saving…">Save privacy settings</x-button></div>
    </form>
    <x-section title="Your data" class="mt-6 max-w-3xl">
        <p class="text-sm text-muted">You can ask for a copy of your data or delete your account. Deleting removes your profile, listings, requests and uploaded documents; some records may be kept where the law requires.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-button :href="route('contact')" variant="neutral" icon="file">Request my data</x-button>
            <x-button variant="danger-outline" icon="trash" data-dialog-open="delete-account">Delete account</x-button>
        </div>
    </x-section>
    <x-confirm-dialog id="delete-account" title="Delete your account?" :action="route('account.destroy')" method="DELETE" confirm="Delete account" variant="danger">This cannot be undone. Active listings and requests are cancelled and other members are notified.
        <label for="delete-password" class="label mt-4">Enter your password to confirm</label>
        <input id="delete-password" type="password" name="password" required autocomplete="current-password" class="field">
    </x-confirm-dialog>
</x-layouts.app>
