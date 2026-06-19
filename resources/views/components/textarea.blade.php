@props([
    'label' => null,
    'name',
    'value' => null,
    'required' => false,
    'rows' => 4,
    'hint' => null,
])
<div class="field">
    @if($label)
        <label for="{{ $name }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        {{ $attributes->class(['textarea', 'is-invalid' => $errors->has($name)]) }}
    >{{ old($name, $value) }}</textarea>
    @if($hint)<span class="field__hint">{{ $hint }}</span>@endif
    @error($name)<span class="field__error">{{ $message }}</span>@enderror
</div>
