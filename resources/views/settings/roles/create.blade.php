@extends('layouts.app')
@section('title', 'เพิ่มบทบาท')

@php
    $groupLabels = [
        'employees' => 'พนักงาน',
        'departments' => 'แผนก',
        'positions' => 'ตำแหน่งงาน',
        'leave-types' => 'ประเภทการลา',
        'leave-requests' => 'คำขอลา',
        'leave-approvals' => 'การอนุมัติลา',
        'attendance' => 'การลงเวลา',
        'announcements' => 'ประกาศ',
        'reports' => 'รายงาน',
        'audit-logs' => 'บันทึกการใช้งาน',
        'users' => 'ผู้ใช้งาน',
        'roles' => 'บทบาท',
    ];
    $checkedPermissions = old('permissions', []);
@endphp

@section('content')
    <x-page-header title="เพิ่มบทบาท" subtitle="สร้างบทบาทใหม่และกำหนดสิทธิ์การเข้าถึง">
        <x-slot:actions>
            <x-button :href="route('settings.roles.index')" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.roles.store') }}">
        @csrf

        <x-card title="ข้อมูลบทบาท">
            <div class="form-grid">
                <x-input name="name" label="ชื่อบทบาท" :required="true" />
                <x-input name="slug" label="รหัสบทบาท (slug)" :required="true" hint="ตัวอักษร ตัวเลข ขีดกลาง หรือขีดล่าง" />
                <div class="col-span-2">
                    <x-textarea name="description" label="คำอธิบาย" :rows="2" />
                </div>
            </div>
        </x-card>

        <div style="margin-top:18px;">
            <x-card title="สิทธิ์การเข้าถึง" subtitle="เลือกสิทธิ์ที่บทบาทนี้สามารถใช้งานได้">
                @error('permissions')<span class="field__error">{{ $message }}</span>@enderror
                @error('permissions.*')<span class="field__error">{{ $message }}</span>@enderror

                @forelse($permissionGroups as $group => $permissions)
                    <div style="margin-bottom:18px;">
                        <div class="cell-strong" style="margin-bottom:10px;">{{ $groupLabels[$group] ?? $group }}</div>
                        <div class="form-grid">
                            @foreach($permissions as $permission)
                                <label class="checkbox-row">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                        @checked(in_array($permission->id, $checkedPermissions)) >
                                    <span>{{ $permission->description ?: $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <x-empty icon="lock" title="ยังไม่มีสิทธิ์ในระบบ" />
                @endforelse
            </x-card>
        </div>

        <div class="flex items-center gap-2" style="margin-top:18px;">
            <x-button type="submit" icon="check">บันทึก</x-button>
            <x-button :href="route('settings.roles.index')" variant="ghost">ยกเลิก</x-button>
        </div>
    </form>
@endsection
