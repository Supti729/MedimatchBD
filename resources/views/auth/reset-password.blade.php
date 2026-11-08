<x-layouts.auth title="Choose a new password" heading="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" data-loading class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email address" :value="$email" required autocomplete="email" maxlength="150" />
        <x-form.password label="New password" autocomplete="new-password" hint="At least 8 characters, with letters and numbers." />
        <x-form.password name="password_confirmation" label="Confirm new password" autocomplete="new-password" />
        <x-button type="submit" size="lg" class="w-full" data-loading-text="Saving…">Reset password</x-button>
    </form>
</x-layouts.auth>
