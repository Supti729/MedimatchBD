@props(['name', 'label', 'checked' => false, 'value' => '1', 'hint' => null, 'id' => null, 'required' => false])
@php $id ??= str_replace(['[', ']', '.'], '_', $name).'_'.\Illuminate\Support\Str::slug($value); @endphp
<label for="{{ $id }}" {{ $attributes->merge(['class' => 'flex cursor-pointer items-start gap-3']) }}>
    <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked)) @required($required) class="mt-0.5 size-5 shrink-0 rounded border-line accent-brand-600">
    <span class="text-sm"><span class="font-medium">{{ $label }}</span>@if ($hint)<span class="block text-muted">{{ $hint }}</span>@endif</span>
</label>
