@extends('layouts.app')
@section('title', 'การแสดงผล')

@section('content')
    <x-page-header title="การแสดงผล" subtitle="เลือกธีมสีและโหมดสว่าง–มืด การตั้งค่านี้ผูกกับบัญชีของคุณ ใช้ได้ทุกเครื่อง">
        <x-slot:breadcrumb><a href="{{ route('settings.index') }}">← ตั้งค่า</a></x-slot:breadcrumb>
    </x-page-header>

    <form method="POST" action="{{ route('settings.appearance.update') }}" id="appearance-form">
        @csrf
        @method('PUT')

        <div class="grid grid--2">
            <div class="grid content-start">
                <x-card title="ธีมสี" subtitle="สีหลักที่ใช้ทั้งระบบ — ปุ่ม ลิงก์ กราฟ และแถบเมนู">
                    <div class="swatches">
                        @foreach($themes as $key => $theme)
                            <label class="swatch {{ $user->theme === $key ? 'is-picked' : '' }}">
                                <input type="radio" name="theme" value="{{ $key }}" @checked($user->theme === $key)>
                                <span class="swatch__chip" style="--sw-ink: {{ $theme['ink'] }}; --sw-from: {{ $theme['from'] }}; --sw-to: {{ $theme['to'] }};">
                                    <i class="swatch__ink"></i>
                                    <i class="swatch__grad"></i>
                                </span>
                                <span class="swatch__text">
                                    <span class="swatch__name">{{ $theme['name'] }}</span>
                                    <span class="swatch__note">{{ $theme['note'] }}</span>
                                </span>
                                <x-icon name="check" class="swatch__tick" />
                            </label>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="โหมดการแสดงผล" subtitle="เลือกพื้นสว่าง พื้นมืด หรือให้ตามการตั้งค่าของเครื่อง">
                    @php
                        $modes = [
                            'light' => ['สว่าง', 'พื้นขาว เหมาะกับห้องที่มีแสงมาก', 'sun'],
                            'dark' => ['มืด', 'พื้นเข้ม ถนอมสายตาในที่แสงน้อย', 'moon'],
                            'auto' => ['ตามระบบ', 'สลับอัตโนมัติตามเครื่องของคุณ', 'refresh'],
                        ];
                    @endphp
                    <div class="modes">
                        @foreach($modes as $key => [$label, $note, $icon])
                            <label class="mode {{ $user->color_scheme === $key ? 'is-picked' : '' }}">
                                <input type="radio" name="color_scheme" value="{{ $key }}" @checked($user->color_scheme === $key)>
                                <x-icon :name="$icon" />
                                <span class="mode__name">{{ $label }}</span>
                                <span class="mode__note">{{ $note }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-card>
            </div>

            {{-- ---------------- Live preview ---------------- --}}
            <div class="grid content-start">
                <x-card title="ตัวอย่าง" subtitle="เปลี่ยนตัวเลือกทางซ้ายแล้วดูผลที่นี่ทันที">
                    <div class="preview" id="appearance-preview">
                        <div class="preview__hero">
                            <div class="preview__eyebrow">แดชบอร์ด</div>
                            <b>สวัสดี, {{ $user->name }}</b>
                            <span class="preview__chip">ลงเวลาเข้า–ออก</span>
                        </div>

                        <div class="preview__kpis">
                            <div class="preview__kpi"><small>พนักงาน</small><b>48</b></div>
                            <div class="preview__kpi preview__kpi--warn"><small>มาสาย</small><b>3</b></div>
                        </div>

                        <div class="preview__row">
                            <span class="badge badge--green badge--dot">อนุมัติแล้ว</span>
                            <span class="badge badge--amber badge--dot">รออนุมัติ</span>
                        </div>

                        <div class="preview__row">
                            <span class="btn btn--primary btn--sm">ปุ่มหลัก</span>
                            <span class="btn btn--secondary btn--sm">ปุ่มรอง</span>
                        </div>

                        <div class="progress"><span style="width:64%"></span></div>
                    </div>

                    <p class="field__hint field-gap">ตัวอย่างนี้ใช้สีจริงของธีม เมื่อกดบันทึกแล้วทั้งระบบจะเปลี่ยนตาม</p>
                </x-card>

                <x-card>
                    <div class="form-actions" style="margin-top:0;">
                        <x-button type="submit" icon="check">บันทึกการแสดงผล</x-button>
                        <x-button href="{{ route('dashboard') }}" variant="ghost">ยกเลิก</x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('appearance-form');
        if (!form) return;

        var root = document.documentElement;
        // Remembered so leaving without saving restores what the account has.
        var saved = { theme: root.dataset.theme, scheme: root.dataset.scheme };
        var media = window.matchMedia('(prefers-color-scheme: dark)');

        function markPicked(input) {
            var name = input.name;
            form.querySelectorAll('input[name="' + name + '"]').forEach(function (radio) {
                radio.closest('label').classList.toggle('is-picked', radio.checked);
            });
        }

        form.addEventListener('change', function (e) {
            if (e.target.name === 'theme') {
                root.dataset.theme = e.target.value;
                markPicked(e.target);
            }
            if (e.target.name === 'color_scheme') {
                // `auto` has no stylesheet of its own — resolve it the same way
                // the boot script does so the preview tells the truth.
                root.dataset.scheme = e.target.value === 'auto'
                    ? (media.matches ? 'dark' : 'light')
                    : e.target.value;
                markPicked(e.target);
            }
        });

        // Navigating away without saving must not leave the console restyled.
        form.addEventListener('submit', function () { saved = null; });
        window.addEventListener('pagehide', function () {
            if (!saved) return;
            root.dataset.theme = saved.theme;
            root.dataset.scheme = saved.scheme;
        });
    })();
</script>
@endpush
