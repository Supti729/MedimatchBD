<x-layouts.auth title="Admin log in" heading="Administrator log in" subheading="For authorised administrators and reviewers only." variant="admin">
    <form method="POST" action="{{ route('admin.login.attempt') }}" data-loading class="space-y-5">
        @csrf
        <x-form.input name="login" type="email" label="Admin email" required autocomplete="username" maxlength="150" autofocus />
        <x-form.password />
        <x-button type="submit" size="lg" class="w-full" icon="lock" data-loading-text="Checking…">Log in</x-button>
        <p class="text-xs text-muted">Admin access is never granted by sign-up. Accounts are assigned roles by an existing administrator, and every sign-in is logged.</p>
    </form>
    <x-slot:footer><a href="{{ route('home') }}" class="link">Back to Medimatch BD</a></x-slot:footer>
</x-layouts.auth>
