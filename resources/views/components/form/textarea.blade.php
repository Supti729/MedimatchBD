@props(['name', 'label', 'value' => null, 'hint' => null, 'required' => false, 'rows' => 4, 'maxlength' => 1000, 'id' => null])
@php $id ??= $name; $invalid = $errors->has($name); @endphp
<div {{ $attributes->only('class') }}>
    <div class="flex items-baseline justify-between gap-2">
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@else<span class="font-normal text-muted">(optional)</span>@endif</label>
        <span id="{{ $id }}-count" class="text-xs text-muted tabular-nums" aria-live="polite"></span>
    </div>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" maxlength="{{ $maxlength }}" data-counter="{{ $id }}-count" @required($required)
        @class(['field', 'field-invalid' => $invalid]) @if ($hint || $invalid) aria-describedby="{{ $id }}-help" @endif {{ $attributes->except('class') }}>{{ old($name, $value) }}</textarea>
    @if ($invalid)
        <p id="{{ $id }}-help" class="mt-1 text-sm font-medium text-red-700">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-help" class="hint">{{ $hint }}</p>
    @endif
</div>
