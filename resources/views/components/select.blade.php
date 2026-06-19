@props([
    'label' => null,
    'name',
    'options' => [],        // [value => text]
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'hint' => null,
])
<div class="field">
    @if($label)
        <label for="{{ $name }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>
    @endif
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->class(['select', 'is-invalid' => $errors->has($name)]) }}
    >
        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $val => $text)
            <option value="{{ $val }}" @selected((string) old($name, $selected) === (string) $val)>{{ $text }}</option>
        @endforeach
    </select>
    @if($hint)<span class="field__hint">{{ $hint }}</span>@endif
    @error($name)<span class="field__error">{{ $message }}</span>@enderror
</div>
