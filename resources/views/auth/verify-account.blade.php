<x-layouts.auth title="Verify your account" heading="Verify your account" subheading="Enter the 6-digit code to confirm your email or phone number.">
    <x-alert type="warning" title="Development mode" class="mb-5">No email or SMS provider is configured, so no code is actually sent. In Part 2 the development code is shown on screen and in the application log, and is clearly labelled as such.</x-alert>
    <form method="POST" action="{{ route('verification.verify') }}" data-loading class="space-y-5">
        @csrf
        <x-form.input name="code" label="Verification code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="[&_input]:text-center [&_input]:text-2xl [&_input]:tracking-[0.5em]" />
        <x-button type="submit" size="lg" class="w-full" data-loading-text="Checking…">Verify</x-button>
    </form>
    <form method="POST" action="{{ route('verification.verify') }}" class="mt-4 text-center">@csrf<input type="hidden" name="resend" value="1"><button class="text-sm font-semibold text-brand-700 hover:underline">Send a new code</button></form>
</x-layouts.auth>
