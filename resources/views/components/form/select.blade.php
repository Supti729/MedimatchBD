{{-- $options: [value => label] or a list of labels (value = label). --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false, 'id' => null, 'srOnlyLabel' => false])
@php
    $id ??= str_replace(['[', ']', '.'], '_', $name);
    $invalid = $errors->has($name);
    $selected = (string) old($name, $value);
    $options = array_is_list($options) ? array_combine($options, $options) : $options;
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" @class(['label', 'sr-only' => $srOnlyLabel])>{{ $label }} @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required) @class(['field', 'field-invalid' => $invalid]) @if ($hint || $invalid) aria-describedby="{{ $id }}-help" @endif {{ $attributes->except('class') }}>
        @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($invalid)
        <p id="{{ $id }}-help" class="mt-1 text-sm font-medium text-red-700">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-help" class="hint">{{ $hint }}</p>
    @endif
</div>
