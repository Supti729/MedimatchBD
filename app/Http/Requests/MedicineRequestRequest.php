<?php

namespace App\Http\Requests;

use App\Enums\MedicineEligibility;
use App\Http\Requests\Concerns\ResolvesLocation;
use App\Models\MedicineRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Validation for creating/editing a medicine request. */
class MedicineRequestRequest extends FormRequest
{
    use ResolvesLocation;

    public const FORMS = ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Powder', 'Drops', 'Ointment', 'Cream', 'Inhaler', 'Injection'];

    /** Ownership is checked BEFORE validation, so non-owners get 403, not validation errors. */
    public function authorize(): bool
    {
        $record = $this->route('medicineRequest');

        return $record === null || $this->user()->can('update', $record);
    }

    public function rules(): array
    {
        return [
            'medicine_id' => ['nullable', 'integer', Rule::exists('medicines', 'id')->where('is_active', true)->whereNot('eligibility', MedicineEligibility::NotAllowed->value)],
            'medicine_name' => ['required', 'string', 'max:100'],
            'generic_name' => ['nullable', 'string', 'max:100'],
            'strength' => ['required', 'string', 'max:30'],
            'dosage_form' => ['required', Rule::in(self::FORMS)],
            'quantity' => ['required', 'string', 'max:40'],
            'preference' => ['required', Rule::in(array_keys(MedicineRequest::PREFERENCES))],
            'notes' => ['nullable', 'string', 'max:500'],
            ...$this->locationRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateLocation($validator);
    }

    public function requestData(): array
    {
        return [
            ...$this->safe()->only(['medicine_id', 'medicine_name', 'generic_name', 'strength', 'dosage_form', 'quantity', 'preference', 'notes']),
            'location_id' => $this->location()->id,
        ];
    }
}
