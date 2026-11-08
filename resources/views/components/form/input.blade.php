@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'id' => null])
@php $id ??= str_replace(['[', ']', '.'], '_', $name); $invalid = $errors->has($name); @endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@else<span class="font-normal text-muted">(optional)</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required)
        @class(['field', 'field-invalid' => $invalid]) @if ($hint || $invalid) aria-describedby="{{ $id }}-help" @endif @if ($invalid) aria-invalid="true" @endif
        {{ $attributes->except('class') }}>
    @if ($invalid)
        <p id="{{ $id }}-help" class="mt-1 text-sm font-medium text-red-700">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-help" class="hint">{{ $hint }}</p>
    @endif
</div>
