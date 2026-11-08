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
                <x-form.checkbox name="ai_matching" label="Use AI-assisted matching" :checked="$settings['ai_matching']" hint="Ranks matches by meaning, suggests catalogue medicines for misspelled or Bangla requests and searches. Identity, strength, form, eligibility and availability rules always apply first. If the service is unreachable, keyword matching is used automatically." />
                <div @class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', 'border-green-200 bg-green-50 text-green-900' => $aiHealth['ok'], 'border-warm-500/40 bg-warm-100 text-warm-700' => ! $aiHealth['ok']])>
                    <x-icon :name="$aiHealth['ok'] ? 'check' : 'alert'" class="mt-0.5 size-5" />
                    <div><p class="font-semibold">Matching service: {{ $aiHealth['message'] }}</p>
                        <p>{{ $aiHealth['model'] ?? '' }}{{ isset($aiHealth['model']) ? ' · ' : '' }}{{ config('services.ai.url') ?: 'no URL set' }}</p></div>
                </div>
            </div>
        </x-section>
        <x-section title="Delivery channels">
            <x-info-list :items="['In-app notifications' => 'Enabled', 'Email' => config('mail.default') === 'log' ? 'Development (written to log)' : config('mail.default'), 'SMS' => 'Not configured']" />
        </x-section>
        <div class="flex justify-end"><x-button type="submit" data-loading-text="Saving…">Save settings</x-button></div>
    </form>
</x-layouts.admin>
