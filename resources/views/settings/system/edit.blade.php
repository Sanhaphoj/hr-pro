@extends('layouts.app')
@section('title', 'ตั้งค่า · '.$group['label'])

@php
    $hasFile = collect($group['sections'])
        ->flatMap(fn (array $section) => $section['fields'])
        ->contains(fn (array $field) => $field['type'] === 'image');
@endphp

@section('content')
    <x-page-header :title="$group['label']" :subtitle="$group['description']">
        <x-slot:breadcrumb><a href="{{ route('settings.index') }}">← ตั้งค่า</a></x-slot:breadcrumb>
    </x-page-header>

    <div class="settings">
        <nav class="settings-nav" aria-label="หมวดการตั้งค่า">
            @foreach($groups as $key => $item)
                <a href="{{ route('settings.system.edit', $key) }}" @class(['settings-nav__link', 'is-active' => $key === $groupKey])
                   @if($key === $groupKey) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" /><span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('settings.appearance.edit') }}" class="settings-nav__link">
                <x-icon name="palette" /><span>การแสดงผลของฉัน</span>
            </a>
        </nav>

        <div class="settings-main">
            <form method="POST" action="{{ route('settings.system.update', $groupKey) }}" id="settings-form" novalidate
                  @if($hasFile) enctype="multipart/form-data" @endif>
                @csrf
                @method('PUT')

                @foreach($group['sections'] as $section)
                    <x-card :title="$section['title']" :subtitle="$section['description'] ?? null">
                        <div class="settings-fields">
                            @foreach($section['fields'] as $name => $field)
                                @include('settings.system._field', ['name' => $name, 'field' => $field])
                            @endforeach
                        </div>
                    </x-card>
                @endforeach

                <div class="settings-bar">
                    <span class="settings-bar__state" id="settings-state">
                        @if($lastChange)
                            แก้ไขล่าสุด {{ thai_date($lastChange->updated_at, 'short', true) }} · {{ $lastChange->updater?->name ?? 'ระบบ' }}
                        @else
                            ใช้ค่าเริ่มต้นของระบบทั้งหมด
                        @endif
                    </span>
                    <x-button type="submit" icon="check">บันทึกการตั้งค่า</x-button>
                </div>
            </form>

            @if($groupKey === 'notifications')
                <x-card title="ทดสอบการส่งอีเมล" subtitle="ส่งอีเมลทดสอบไปที่ {{ auth()->user()->email }} ด้วยการตั้งค่าที่บันทึกแล้ว">
                    <form method="POST" action="{{ route('settings.system.test-mail') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="send">ส่งอีเมลทดสอบ</x-button>
                    </form>
                </x-card>
            @endif

            <div class="settings-reset">
                <div>
                    <b>คืนค่าเริ่มต้นของหมวดนี้</b>
                    <span>ล้างค่าที่กำหนดเองทั้งหมดในหมวด “{{ $group['label'] }}” กลับไปใช้ค่าจากไฟล์ตั้งค่าของระบบ</span>
                </div>
                <form method="POST" action="{{ route('settings.system.reset', $groupKey) }}"
                      data-confirm="คืนค่าเริ่มต้นทั้งหมดในหมวด “{{ $group['label'] }}” ใช่หรือไม่?">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="ghost" icon="rotate-ccw" :disabled="! in_array(true, $customised, true)">คืนค่าเริ่มต้น</x-button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('settings-form');
        if (!form) return;

        // ----- Unsaved changes ------------------------------------------------
        var state = document.getElementById('settings-state');
        var savedText = state.textContent;

        function snapshot() {
            var parts = [];
            new FormData(form).forEach(function (value, key) {
                if (key === '_token' || key === '_method') return;
                parts.push(key + '=' + (value instanceof File ? value.name : value));
            });
            return parts.join('&');
        }

        var clean = snapshot();
        var dirty = false;

        function check() {
            var now = snapshot() !== clean;
            if (now === dirty) return;
            dirty = now;
            form.classList.toggle('is-dirty', dirty);
            state.textContent = dirty ? 'มีการเปลี่ยนแปลงที่ยังไม่บันทึก' : savedText;
        }

        form.addEventListener('input', check);
        form.addEventListener('change', check);
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });

        // ----- Colour picker mirrored into its text box -------------------------
        form.querySelectorAll('[data-color]').forEach(function (wrap) {
            var picker = wrap.querySelector('input[type="color"]');
            var text = wrap.querySelector('input[type="text"]');
            picker.addEventListener('input', function () { text.value = picker.value; check(); });
            text.addEventListener('input', function () {
                if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value;
            });
        });

        // ----- Preview a newly chosen logo ---------------------------------------
        form.querySelectorAll('[data-image]').forEach(function (wrap) {
            var input = wrap.querySelector('input[type="file"]');
            var box = wrap.querySelector('.input-image__preview');
            input.addEventListener('change', function () {
                if (!input.files || !input.files[0]) return;
                var img = document.createElement('img');
                img.alt = '';
                img.src = URL.createObjectURL(input.files[0]);
                box.replaceChildren(img);
            });
        });

        // ----- Fields that only matter when another one is set -------------------
        var dependants = form.querySelectorAll('[data-depends]');
        function controlValue(name) {
            var boxes = form.querySelectorAll('[name="' + name + '"]');
            var last = boxes[boxes.length - 1];
            if (!last) return null;
            if (last.type === 'checkbox') return last.checked ? '1' : '0';
            return last.value;
        }
        function applyDepends() {
            dependants.forEach(function (el) {
                var rule = el.dataset.depends.split(':');
                el.classList.toggle('is-muted', controlValue(rule[0]) !== rule[1]);
            });
        }
        if (dependants.length) {
            form.addEventListener('change', applyDepends);
            applyDepends();
        }
    })();
</script>
@endpush
