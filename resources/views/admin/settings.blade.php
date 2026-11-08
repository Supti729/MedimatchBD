<x-layouts.admin title="Settings">
    <x-page-header title="Admin settings" description="Platform-wide rules. Changes are recorded in the audit log." />
    <form method="POST" action="{{ route('admin.settings.update') }}" data-loading class="max-w-3xl space-y-6">
        @csrf @method('PATCH')
        <x-section title="Listings">
            <div class="space-y-4">
                <x-form.checkbox name="review_new_members" label="Review all listings from members younger than 30 days" :checked="true" />
                <x-form.checkbox name="review_verification_required" label="Always review medicines marked Verification Required" :checked="true" />
                <x-form.checkbox name="allow_affordable_access" label="Allow Affordable Access listings" hint="Only for approved partner programmes. Off for individual members." />
                <x-form.input name="min_expiry_months" type="number" label="Minimum months until expiry" value="1" min="1" max="12" required />
            </div>
        </x-section>
        <x-section title="Requests and matching">
            <div class="space-y-4">
                <x-form.input name="request_expiry_days" type="number" label="Requests expire after (days)" value="30" min="7" max="90" required />
                <x-form.checkbox name="ai_matching" label="Use AI-assisted ranking" hint="Available after Part 3. Hard eligibility filters always apply first." />
            </div>
        </x-section>
        <x-section title="Notifications">
            <x-info-list :items="['In-app notifications' => 'Enabled', 'Email provider' => 'Not configured', 'SMS provider' => 'Not configured']" />
        </x-section>
        <div class="flex justify-end"><x-button type="submit" data-loading-text="Saving…">Save settings</x-button></div>
    </form>
</x-layouts.admin>
