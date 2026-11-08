<x-layouts.admin title="Settings">
    <x-page-header title="Admin settings" description="Platform-wide rules. Changes are recorded in the audit log." />
    <form method="POST" action="{{ route('admin.settings.update') }}" data-loading class="max-w-3xl space-y-6">
        @csrf @method('PATCH')
        <x-section title="Listings">
            <div class="space-y-4">
                <x-form.checkbox name="review_new_members" label="Review listings from members younger than 30 days (unless identity-verified)" :checked="$settings['review_new_members']" />
                <x-form.checkbox name="review_verification_required" label="Always review medicines marked Verification Required" :checked="$settings['review_verification_required']" />
                <x-form.checkbox name="allow_affordable_access" label="Allow Affordable Access listings" hint="Only switch on for an approved partner programme." :checked="$settings['allow_affordable_access']" />
                <x-form.input name="min_expiry_months" type="number" label="Minimum months until expiry" :value="$settings['min_expiry_months']" min="1" max="12" required />
            </div>
        </x-section>
        <x-section title="Requests and matching">
            <div class="space-y-4">
                <x-form.input name="request_expiry_days" type="number" label="New requests expire after (days)" :value="$settings['request_expiry_days']" min="7" max="90" required />
                <label class="flex items-start gap-3 opacity-60"><input type="checkbox" disabled class="mt-0.5 size-5"><span class="text-sm"><span class="font-medium">Use AI-assisted ranking</span><span class="block text-muted">Available after Part 3. Hard eligibility rules always apply first.</span></span></label>
            </div>
        </x-section>
        <x-section title="Delivery channels">
            <x-info-list :items="['In-app notifications' => 'Enabled', 'Email' => config('mail.default') === 'log' ? 'Development (written to log)' : config('mail.default'), 'SMS' => 'Not configured']" />
        </x-section>
        <div class="flex justify-end"><x-button type="submit" data-loading-text="Saving…">Save settings</x-button></div>
    </form>
</x-layouts.admin>
