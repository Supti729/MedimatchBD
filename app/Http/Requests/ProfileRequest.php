<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesLocation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    use ResolvesLocation;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? strtolower(trim($this->input('email'))) : null,
            'phone' => User::normalisePhone($this->input('phone')) ?? ($this->filled('phone') ? 'invalid' : null),
        ]);
    }

    public function rules(): array
    {
        $id = $this->user()->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'required_without:email', 'regex:/^\+8801[3-9]\d{8}$/', Rule::unique('users', 'phone')->ignore($id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ...$this->locationRules(),
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.'];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateLocation($validator);
    }
}
