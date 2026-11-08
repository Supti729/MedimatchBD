{{-- Division → District → Upazila cascade (see initLocationSelects in resources/js/app.js). --}}
@props(['division' => null, 'district' => null, 'upazila' => null, 'required' => false, 'withUpazila' => true, 'prefix' => '', 'compact' => false])
@once
    <script id="bd-locations" type="application/json">@json(config('locations'))</script>
@endonce
@php $p = $prefix ? $prefix.'_' : ''; @endphp
<div data-location-group {{ $attributes->merge(['class' => $compact ? 'space-y-4' : 'grid gap-4 '.($withUpazila ? 'sm:grid-cols-3' : 'sm:grid-cols-2')]) }}>
    <div>
        <label for="{{ $p }}division" class="label">Division @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
        <select id="{{ $p }}division" name="division" data-location="division" @required($required) @class(['field', 'field-invalid' => $errors->has('division')])>
            <option value="">{{ $required ? 'Select division' : 'All divisions' }}</option>
            @foreach (array_keys(config('locations')) as $name)
                <option value="{{ $name }}" @selected(old('division', $division) === $name)>{{ $name }}</option>
            @endforeach
        </select>
        @error('division')<p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $p }}district" class="label">District @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
        {{-- Options are filled by JavaScript; the server-rendered option keeps the value if JS is off. --}}
        <select id="{{ $p }}district" name="district" data-location="district" data-placeholder="{{ $required ? 'Select district' : 'All districts' }}" data-selected="{{ old('district', $district) }}" @required($required) @class(['field', 'field-invalid' => $errors->has('district')])>
            <option value="">{{ $required ? 'Select district' : 'All districts' }}</option>
            @if (old('district', $district))<option value="{{ old('district', $district) }}" selected>{{ old('district', $district) }}</option>@endif
        </select>
        @error('district')<p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
    </div>
    @if ($withUpazila)
        <div>
            <label for="{{ $p }}upazila" class="label">Upazila / Thana @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
            <select id="{{ $p }}upazila" name="upazila" data-location="upazila" data-placeholder="{{ $required ? 'Select upazila' : 'All upazilas' }}" data-selected="{{ old('upazila', $upazila) }}" @required($required) @class(['field', 'field-invalid' => $errors->has('upazila')])>
                <option value="">{{ $required ? 'Select upazila' : 'All upazilas' }}</option>
                @if (old('upazila', $upazila))<option value="{{ old('upazila', $upazila) }}" selected>{{ old('upazila', $upazila) }}</option>@endif
            </select>
            @error('upazila')<p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
        </div>
    @endif
</div>
