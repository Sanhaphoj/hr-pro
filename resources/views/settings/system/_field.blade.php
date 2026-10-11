@php
    $type = $field['type'];
    $id = 'setting-'.$name;
    $value = old($name, $values[$name] ?? null);
    $required = in_array('required', $field['rules'] ?? [], true);
    $wide = in_array($type, ['textarea', 'days', 'multi', 'image'], true);
    $depends = isset($field['depends']) ? array_key_first($field['depends']).':'.reset($field['depends']) : null;
    $invalid = $errors->has($name) || $errors->has($name.'.*');
    $isCustom = $customised[$name] ?? false;
@endphp
<div @class(['setting', 'setting--wide' => $wide, 'setting--toggle' => $type === 'toggle', 'has-error' => $invalid])
     @if($depends) data-depends="{{ $depends }}" @endif>

    @if($type === 'toggle')
        <div class="setting__switch-row">
            <div class="setting__text">
                <label for="{{ $id }}" class="setting__label">{{ $field['label'] }}</label>
                @if(! empty($field['hint']))<p class="setting__hint">{{ $field['hint'] }}</p>@endif
            </div>
            @if($isCustom)<span class="setting__custom" title="ต่างจากค่าเริ่มต้นของระบบ">กำหนดเอง</span>@endif
            <span class="switch">
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" role="switch" @checked((bool) $value)>
                <span class="switch__track" aria-hidden="true"></span>
            </span>
        </div>
    @else
        <div class="setting__head">
            <label for="{{ $id }}" class="setting__label">{{ $field['label'] }}@if($required)<span class="req">*</span>@endif</label>
            @if($isCustom)<span class="setting__custom" title="ต่างจากค่าเริ่มต้นของระบบ">กำหนดเอง</span>@endif
        </div>

        @switch($type)
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="textarea">{{ $value }}</textarea>
                @break

            @case('number')
                <div class="input-unit">
                    <input type="number" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="input"
                           step="{{ $field['step'] ?? 1 }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                    @if(! empty($field['unit']))<span class="input-unit__suffix">{{ $field['unit'] }}</span>@endif
                </div>
                @break

            @case('select')
                <select id="{{ $id }}" name="{{ $name }}" class="select">
                    @foreach($field['options'] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @break

            @case('days')
                @php
                    $picked = array_map('intval', (array) $value);
                    $dayNames = [1 => 'จันทร์', 2 => 'อังคาร', 3 => 'พุธ', 4 => 'พฤหัสบดี', 5 => 'ศุกร์', 6 => 'เสาร์', 7 => 'อาทิตย์'];
                @endphp
                <div class="pills" role="group" aria-label="{{ $field['label'] }}" id="{{ $id }}">
                    @foreach($dayNames as $day => $dayName)
                        <label class="pill">
                            <input type="checkbox" name="{{ $name }}[]" value="{{ $day }}" @checked(in_array($day, $picked, true))>
                            <span>{{ $dayName }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('multi')
                @php $picked = array_map('strval', (array) $value); @endphp
                <div class="pills" role="group" aria-label="{{ $field['label'] }}" id="{{ $id }}">
                    @foreach($field['options'] as $optionValue => $optionLabel)
                        <label class="pill">
                            <input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}" @checked(in_array((string) $optionValue, $picked, true))>
                            <span>{{ $optionLabel }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('color')
                <div class="input-color" data-color>
                    <input type="color" value="{{ $value ?: '#0e8f88' }}" aria-label="เลือกสี">
                    <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="input" maxlength="7" spellcheck="false">
                </div>
                @break

            @case('image')
                <div class="input-image" data-image>
                    <div class="input-image__preview">
                        @if($value && is_file(public_path($value)))
                            <img src="{{ asset($value) }}" alt="">
                        @else
                            <span>{{ $field['empty'] ?? 'ยังไม่มีโลโก้' }}</span>
                        @endif
                    </div>
                    <div class="input-image__actions">
                        <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/png,image/jpeg,image/webp" class="input">
                        @if($value)
                            <label class="checkbox-row">
                                <input type="checkbox" name="{{ $name }}_remove" value="1">
                                <span>ลบรูปปัจจุบัน</span>
                            </label>
                        @endif
                    </div>
                </div>
                @break

            @case('password')
                <input type="password" id="{{ $id }}" name="{{ $name }}" class="input" autocomplete="new-password"
                       placeholder="{{ ($hasSecret[$name] ?? false) ? '•••••••• ตั้งค่าไว้แล้ว' : '' }}">
                @if($hasSecret[$name] ?? false)
                    <label class="checkbox-row setting__sub">
                        <input type="checkbox" name="{{ $name }}_clear" value="1">
                        <span>ล้างรหัสผ่านที่บันทึกไว้</span>
                    </label>
                @endif
                @break

            @default
                <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="input"
                       placeholder="{{ $field['placeholder'] ?? '' }}">
        @endswitch

        @if(! empty($field['hint']))<p class="setting__hint">{{ $field['hint'] }}</p>@endif
    @endif

    @error($name)<p class="field__error">{{ $message }}</p>@enderror
    @error($name.'.*')<p class="field__error">{{ $message }}</p>@enderror
</div>
