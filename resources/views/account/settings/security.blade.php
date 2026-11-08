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
            <x-info-list :items="[
                'Email' => $me->email ? $me->email.' · '.($me->email_verified_at ? 'verified' : 'not verified') : 'Not added',
                'Phone' => $me->phone ? $me->phone_display.' · '.($me->phone_verified_at ? 'verified' : 'not verified') : 'Not added',
            ]" />
            <p class="mt-3 text-sm text-muted">Codes are issued in development mode: no SMS provider is configured and email is written to the application log.</p>
        </x-section>
        <x-section title="Active sessions">
            <ul class="divide-y divide-line text-sm">
                @foreach ($sessions as $session)
                    <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <span><span class="font-semibold">{{ $session->agent }}</span><span class="block text-muted">{{ $session->ip }} · {{ $session->current ? 'active now' : $session->last_active->diffForHumans() }}</span></span>
                        @if ($session->current)<span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">This device</span>@endif
                    </li>
                @endforeach
            </ul>
            @if ($sessions->count() > 1)
                <form method="POST" action="{{ route('settings.sessions.destroy') }}" class="mt-4">@csrf @method('DELETE')<x-button type="submit" variant="neutral" icon="logout">Log out other sessions</x-button></form>
            @endif
        </x-section>
    </div>
</x-layouts.app>
