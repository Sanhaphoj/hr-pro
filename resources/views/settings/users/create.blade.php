@extends('layouts.app')
@section('title', 'เพิ่มผู้ใช้งาน')

@section('content')
    <x-page-header title="เพิ่มผู้ใช้งาน" subtitle="สร้างบัญชีผู้ใช้งานใหม่และกำหนดบทบาท">
        <x-slot:actions>
            <x-button :href="route('settings.users.index')" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.users.store') }}">
        @csrf

        <x-card title="ข้อมูลบัญชี">
            <div class="form-grid">
                <x-input name="name" label="ชื่อ–นามสกุล" :required="true" />
                <x-input name="email" type="email" label="อีเมล" :required="true" />
                <x-input name="password" type="password" label="รหัสผ่าน" :required="true" hint="อย่างน้อย 8 ตัวอักษร" autocomplete="new-password" />
                <x-input name="password_confirmation" type="password" label="ยืนยันรหัสผ่าน" :required="true" autocomplete="new-password" />
                <div class="col-span-2">
                    <x-checkbox name="is_active" label="เปิดใช้งานบัญชี" :checked="true" />
                </div>
            </div>
        </x-card>

        <div style="margin-top:18px;">
            <x-card title="บทบาท" subtitle="กำหนดบทบาทเพื่อให้สิทธิ์การเข้าถึงระบบ">
                @if($roles->isEmpty())
                    <x-empty icon="shield" title="ยังไม่มีบทบาท" message="กรุณาสร้างบทบาทก่อนกำหนดให้ผู้ใช้งาน" />
                @else
                    <div class="form-grid">
                        @foreach($roles as $role)
                            <label class="checkbox-row">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    @checked(in_array($role->id, old('roles', []))) >
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')<span class="field__error">{{ $message }}</span>@enderror
                    @error('roles.*')<span class="field__error">{{ $message }}</span>@enderror
                @endif
            </x-card>
        </div>

        <div class="flex items-center gap-2" style="margin-top:18px;">
            <x-button type="submit" icon="check">บันทึก</x-button>
            <x-button :href="route('settings.users.index')" variant="ghost">ยกเลิก</x-button>
        </div>
    </form>
@endsection
