<x-layouts.auth title="Verify your account" heading="Verify your account" :subheading="'Enter the 6-digit code we issued for your '.($channel === 'email' ? 'email address' : 'phone number').'.'">
    @if (config('medimatch.show_dev_codes'))
        <x-alert type="warning" title="Development mode" class="mb-5">
            No SMS provider is configured and email is written to the application log, so nothing is actually delivered.
            @if (session('dev_code'))
                Your code is <strong class="font-mono text-base tracking-widest">{{ session('dev_code') }}</strong>.
            @else
                Request a new code below to see it here.
            @endif
        </x-alert>
    @endif
    <form method="POST" action="{{ route('verification.verify') }}" data-loading class="space-y-5">
        @csrf
        <x-form.input name="code" label="Verification code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus class="[&_input]:text-center [&_input]:text-2xl [&_input]:tracking-[0.5em]" />
        <x-button type="submit" size="lg" class="w-full" data-loading-text="Checking…">Verify</x-button>
    </form>
    <div class="mt-4 flex items-center justify-between gap-3 text-sm">
        <form method="POST" action="{{ route('verification.resend') }}">@csrf<button class="font-semibold text-brand-700 hover:underline">Send a new code</button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-muted hover:underline">Log out</button></form>
    </div>
</x-layouts.auth>
