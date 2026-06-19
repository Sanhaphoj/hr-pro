@extends('layouts.app')
@section('title', 'โปรไฟล์ของฉัน')

@section('content')
    <x-page-header title="โปรไฟล์ของฉัน" subtitle="จัดการข้อมูลบัญชีและรหัสผ่านของคุณ" />

    <div class="grid grid--halves">
        <x-card title="ข้อมูลบัญชี">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom:16px;">
                    <x-input name="name" label="ชื่อ–นามสกุล" :value="$user->name" :required="true" />
                </div>
                <div class="field" style="margin-bottom:16px;">
                    <x-input name="email" type="email" label="อีเมล" :value="$user->email" :required="true" />
                </div>
                <x-button type="submit" icon="check">บันทึกข้อมูล</x-button>
            </form>
        </x-card>

        <x-card title="เปลี่ยนรหัสผ่าน">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div style="margin-bottom:16px;">
                    <x-input name="current_password" type="password" label="รหัสผ่านปัจจุบัน" :required="true" autocomplete="current-password" />
                </div>
                <div style="margin-bottom:16px;">
                    <x-input name="password" type="password" label="รหัสผ่านใหม่" :required="true" hint="อย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวอักษรและตัวเลข" autocomplete="new-password" />
                </div>
                <div style="margin-bottom:16px;">
                    <x-input name="password_confirmation" type="password" label="ยืนยันรหัสผ่านใหม่" :required="true" autocomplete="new-password" />
                </div>
                <x-button type="submit" variant="secondary" icon="lock">เปลี่ยนรหัสผ่าน</x-button>
            </form>
        </x-card>
    </div>

    @if($employee)
        <div style="margin-top:18px;">
            <x-card title="ข้อมูลพนักงานที่เชื่อมโยง" subtitle="ข้อมูลนี้จัดการโดยฝ่ายบุคคล">
                <dl class="dl">
                    <dt>รหัสพนักงาน</dt><dd>{{ $employee->employee_code }}</dd>
                    <dt>ตำแหน่ง</dt><dd>{{ $employee->position?->title ?? '—' }}</dd>
                    <dt>แผนก</dt><dd>{{ $employee->department?->name ?? '—' }}</dd>
                    <dt>สถานะ</dt><dd><x-status-badge type="employee" :value="$employee->status" /></dd>
                    <dt>วันเริ่มงาน</dt><dd>{{ $employee->hire_date?->translatedFormat('j F Y') ?? '—' }}</dd>
                </dl>
            </x-card>
        </div>
    @endif
@endsection
