@props(['name' => 'password', 'label' => 'Password', 'hint' => null, 'autocomplete' => 'current-password', 'id' => null, 'required' => true])
@php $id ??= $name; $invalid = $errors->has($name); @endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="password" autocomplete="{{ $autocomplete }}" @required($required) minlength="8"
            @class(['field pr-16', 'field-invalid' => $invalid]) @if ($hint || $invalid) aria-describedby="{{ $id }}-help" @endif @if ($invalid) aria-invalid="true" @endif>
        <button type="button" data-password-toggle="{{ $id }}" aria-pressed="false" class="absolute inset-y-0 right-0 mt-1 rounded-r-lg px-3 text-sm font-semibold text-brand-700 hover:bg-brand-50">Show</button>
    </div>
    @if ($invalid)
        <p id="{{ $id }}-help" class="mt-1 text-sm font-medium text-red-700">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-help" class="hint">{{ $hint }}</p>
    @endif
</div>
