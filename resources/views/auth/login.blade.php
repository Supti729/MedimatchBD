<x-layouts.auth title="Log in" heading="Welcome back" subheading="Log in with your email address or Bangladeshi phone number.">
    <form method="POST" action="{{ route('login.attempt') }}" data-loading class="space-y-5">
        @csrf
        <x-form.input name="login" label="Email or phone number" required autocomplete="username" maxlength="150" placeholder="you@example.com or 01XXXXXXXXX" autofocus />
        <x-form.password />
        <div class="flex items-center justify-between gap-3">
            <x-form.checkbox name="remember" label="Remember me" />
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-700 hover:underline">Forgot password?</a>
        </div>
        <x-button type="submit" size="lg" class="w-full" data-loading-text="Logging in…">Log in</x-button>
    </form>
    <x-slot:footer>New to Medimatch BD? <a href="{{ route('register') }}" class="link">Create an account</a></x-slot:footer>
</x-layouts.auth>
