@props([
    'label',
    'name',
    'checked' => false,
    'value' => 1,
    'hint' => null,
])
<div class="field">
    <label class="checkbox-row">
        {{-- Hidden field guarantees a value is sent when the box is unchecked. --}}
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked))>
        <span>{{ $label }}</span>
    </label>
    @if($hint)<span class="field__hint">{{ $hint }}</span>@endif
    @error($name)<span class="field__error">{{ $message }}</span>@enderror
</div>
