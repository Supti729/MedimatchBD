<x-layouts.app title="Security Settings">
    <x-page-header title="Settings" />
    @include('account.settings._tabs')
    <div class="max-w-3xl space-y-6">
        <x-section title="Change password">
            <form method="POST" action="{{ route('settings.password.update') }}" data-loading class="space-y-5">
                @csrf @method('PATCH')
                <x-form.password name="current_password" label="Current password" />
                <x-form.password name="password" label="New password" autocomplete="new-password" hint="At least 8 characters, with letters and numbers." />
                <x-form.password name="password_confirmation" label="Confirm new password" autocomplete="new-password" />
                <x-button type="submit" data-loading-text="Updating…">Update password</x-button>
            </form>
        </x-section>
        <x-section title="Account verification">
            <x-info-list :items="['Email' => $me->email.' · verified (development mode)', 'Phone' => $me->phone.' · not verified']" />
            <p class="mt-3 text-sm text-muted">Phone verification needs an SMS provider, which isn't configured for this prototype.</p>
        </x-section>
        <x-section title="Active sessions">
            <ul class="divide-y divide-line text-sm">
                <li class="flex items-center justify-between gap-3 py-3 first:pt-0"><span><span class="font-semibold">This device</span><span class="block text-muted">Chrome on Android · Dhaka · active now</span></span><span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">Current</span></li>
                <li class="flex items-center justify-between gap-3 py-3 last:pb-0"><span><span class="font-semibold">Laptop</span><span class="block text-muted">Firefox on Windows · Dhaka · 2 days ago</span></span></li>
            </ul>
            <form method="POST" action="{{ route('settings.sessions.destroy') }}" class="mt-4">@csrf @method('DELETE')<x-button type="submit" variant="neutral" icon="logout">Log out other sessions</x-button></form>
            <p class="mt-2 text-xs text-muted">Sample sessions for the preview.</p>
        </x-section>
    </div>
</x-layouts.app>
