<x-layouts.app title="Edit profile">
    <x-page-header title="Edit profile" :back="route('profile.show')" back-label="Back to profile" />
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-loading class="space-y-6">
        @csrf @method('PATCH')
        <x-section title="Profile photo">
            <div class="flex flex-wrap items-center gap-5">
                <x-avatar :name="$me->name" size="xl" />
                <div>
                    <label for="avatar" class="label">Upload a new photo</label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="field file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand-700">
                    <p class="hint">JPG, PNG or WebP, up to 2 MB.</p>
                </div>
            </div>
        </x-section>
        <x-section title="Personal details">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Full name" :value="$me->name" required maxlength="100" autocomplete="name" class="sm:col-span-2" />
                <x-form.input name="email" type="email" label="Email address" :value="$me->email" maxlength="150" autocomplete="email" hint="Changing your email requires verifying the new address." />
                <x-form.input name="phone" type="tel" label="Phone number" value="01700000000" autocomplete="tel" hint="Changing your phone requires verifying the new number." />
            </div>
        </x-section>
        <x-section title="Your area" description="Used to show listings near you. Other members only see your district and upazila on listings you create.">
            <x-form.location :division="$me->division" :district="$me->district" :upazila="$me->upazila" required />
        </x-section>
        <div class="flex flex-wrap justify-end gap-2">
            <x-button :href="route('profile.show')" variant="neutral">Cancel</x-button>
            <x-button type="submit" data-loading-text="Saving…">Save changes</x-button>
        </div>
    </form>
</x-layouts.app>
