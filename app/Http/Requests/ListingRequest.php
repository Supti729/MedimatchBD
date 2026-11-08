<?php

namespace App\Http\Requests;

use App\Enums\ListingType;
use App\Http\Requests\Concerns\ResolvesLocation;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListingRequest extends FormRequest
{
    use ResolvesLocation;

    public const UNITS = ['tablets' => 'Tablets', 'capsules' => 'Capsules', 'bottles' => 'Bottles', 'tubes' => 'Tubes', 'inhalers' => 'Inhalers', 'sachets' => 'Sachets', 'other' => 'Other'];

    public const PACKAGING = ['Sealed, undamaged', 'Sealed bottle', 'Outer box opened, strips sealed'];

    public function isDraft(): bool
    {
        return $this->input('intent') === 'draft';
    }

    /** Ownership is checked BEFORE validation, so non-owners get 403, not validation errors. */
    public function authorize(): bool
    {
        $record = $this->route('listing');

        return $record === null || $this->user()->can('update', $record);
    }

    public function rules(): array
    {
        $types = Setting::get('allow_affordable_access') ? array_column(ListingType::cases(), 'value') : [ListingType::Donation->value];

        return [
            'medicine_id' => ['required', 'integer', Rule::exists('medicines', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'unit' => ['required', Rule::in(array_keys(self::UNITS))],
            // Expiry month, e.g. 2027-03. Must be at least N months away.
            'expires_on' => ['required', 'date_format:Y-m', 'after_or_equal:'.now()->addMonths((int) Setting::get('min_expiry_months'))->format('Y-m')],
            'packaging' => ['required', Rule::in(self::PACKAGING)],
            'batch_number' => ['nullable', 'string', 'max:40'],
            'type' => ['required', Rule::in($types)],
            'description' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Declarations are only required when submitting, not for drafts.
            'confirm_sealed' => [Rule::requiredIf(! $this->isDraft()), 'accepted_if:intent,submit'],
            'confirm_expiry' => [Rule::requiredIf(! $this->isDraft()), 'accepted_if:intent,submit'],
            'confirm_free' => [Rule::requiredIf(! $this->isDraft()), 'accepted_if:intent,submit'],
            ...$this->locationRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'expires_on.after_or_equal' => 'Medicine must be at least '.Setting::get('min_expiry_months').' month(s) from expiry.',
            'type.in' => 'Affordable Access is only available to approved partner programmes.',
            'confirm_sealed.required' => 'Please confirm the medicine is sealed and stored correctly.',
            'confirm_expiry.required' => 'Please confirm the expiry date.',
            'confirm_free.required' => 'Please confirm you are not asking for payment.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateLocation($validator);
    }

    /** Data safe to put on the model (status etc. are never taken from the form). */
    public function listingData(): array
    {
        return [
            ...$this->safe()->only(['medicine_id', 'quantity', 'unit', 'packaging', 'batch_number', 'type', 'description']),
            'expires_on' => $this->input('expires_on').'-01',
            'location_id' => $this->location()->id,
        ];
    }
}
