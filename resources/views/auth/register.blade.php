<x-layouts.auth title="Register" heading="Create your account" subheading="One account lets you both request and offer medicine.">
    <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" data-loading class="space-y-5">
        @csrf
        <x-form.input name="name" label="Full name" required autocomplete="name" maxlength="100" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="email" type="email" label="Email address" autocomplete="email" maxlength="150" hint="Email, phone, or both. Never shown to other members." />
            <x-form.input name="phone" type="tel" label="Phone number" autocomplete="tel" placeholder="01XXXXXXXXX" pattern="(\+?880)?0?1[3-9][0-9]{8}" hint="Bangladeshi mobile number." />
        </div>
        <x-form.location required compact />
        <x-form.password label="Password" autocomplete="new-password" hint="At least 8 characters, with letters and numbers." />
        <x-form.password name="password_confirmation" label="Confirm password" autocomplete="new-password" />
        <div>
            <label for="avatar" class="label">Profile picture <span class="font-normal text-muted">(optional)</span></label>
            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="field file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand-700">
            <p class="hint">JPG, PNG or WebP, up to 2 MB. Other members can see this photo.</p>
        </div>
        <x-form.checkbox required name="terms" label="I agree to the Terms and Privacy Policy" hint="Medimatch BD never shows your phone, email or address to other members." />
        <p class="-mt-3 pl-8 text-sm"><a href="{{ route('terms') }}" class="link" target="_blank">Terms</a> · <a href="{{ route('privacy') }}" class="link" target="_blank">Privacy Policy</a></p>
        <x-button type="submit" size="lg" class="w-full" data-loading-text="Creating account…">Create account</x-button>
    </form>
    <x-slot:footer>Already have an account? <a href="{{ route('login') }}" class="link">Log in</a></x-slot:footer>
</x-layouts.auth>
