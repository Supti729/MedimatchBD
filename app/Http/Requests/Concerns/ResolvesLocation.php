<?php

namespace App\Http\Requests\Concerns;

use App\Models\Location;
use Illuminate\Validation\Validator;

/** Shared division/district/upazila validation: the three must form a real location row. */
trait ResolvesLocation
{
    protected function locationRules(): array
    {
        return [
            'division' => ['required', 'string', 'max:40'],
            'district' => ['required', 'string', 'max:40'],
            'upazila' => ['required', 'string', 'max:60'],
        ];
    }

    protected function validateLocation(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $v->errors()->hasAny(['division', 'district', 'upazila']) && ! $this->location()) {
                $v->errors()->add('upazila', 'Choose a valid division, district and upazila.');
            }
        });
    }

    public function location(): ?Location
    {
        return Location::findFor($this->input('division'), $this->input('district'), $this->input('upazila'));
    }
}
