<x-layouts.auth title="Forgot password" heading="Reset your password" subheading="Enter the email address on your account and we'll send a reset link.">
    <form method="POST" action="{{ route('password.email') }}" data-loading class="space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" maxlength="150" autofocus />
        <x-alert type="info">Phone-only accounts: password reset by SMS needs an SMS provider, which is not configured for this prototype. Add an email address to your profile to use this option.</x-alert>
        <x-button type="submit" size="lg" class="w-full" data-loading-text="Sending link…">Send reset link</x-button>
    </form>
    <x-slot:footer><a href="{{ route('login') }}" class="link">Back to log in</a></x-slot:footer>
</x-layouts.auth>
