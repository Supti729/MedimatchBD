<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesLocation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    use ResolvesLocation;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? strtolower(trim($this->input('email'))) : null,
            'phone_raw' => $this->input('phone'),
            'phone' => User::normalisePhone($this->input('phone')) ?? ($this->filled('phone') ? 'invalid' : null),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'required_without:email', 'regex:/^\+8801[3-9]\d{8}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'terms' => ['accepted'],
            ...$this->locationRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Enter an email address or a phone number.',
            'phone.required_without' => 'Enter a phone number or an email address.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'email.unique' => 'An account with this email already exists.',
            'phone.unique' => 'An account with this phone number already exists.',
            'terms.accepted' => 'Please accept the Terms and Privacy Policy.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateLocation($validator);
    }
}
