@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
])
<div class="field">
    @if($label)
        <label for="{{ $name }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>
    @endif
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->class(['input', 'is-invalid' => $errors->has($name)]) }}
    >
    @if($hint)<span class="field__hint">{{ $hint }}</span>@endif
    @error($name)<span class="field__error">{{ $message }}</span>@enderror
</div>
