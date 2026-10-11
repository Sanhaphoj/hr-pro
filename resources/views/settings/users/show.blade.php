@extends('layouts.app')
@section('title', 'รายละเอียดผู้ใช้งาน')

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
        'work-locations' => 'บริเวณลงเวลา',
        'payroll' => 'เงินเดือน',
        'recruitment' => 'สรรหาบุคลากร',
        'performance' => 'ประเมินผล',
        'training' => 'ฝึกอบรม',
        'documents' => 'คลังเอกสาร',
        'settings' => 'การตั้งค่าระบบ',
    ];
    $permissions = $user->roles->flatMap->permissions->unique('id')->sortBy('name');
    $grouped = $permissions->groupBy('group');
@endphp

@section('content')
    <x-page-header title="รายละเอียดผู้ใช้งาน" subtitle="{{ $user->name }}">
        <x-slot:actions>
            @can('users.update')
                <x-button :href="route('settings.users.edit', $user)" variant="secondary" icon="edit">แก้ไข</x-button>
            @endcan
            <x-button :href="route('settings.users.index')" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid--2">
        <div class="grid content-start">
            <x-card title="ข้อมูลบัญชี">
                <div class="flex items-center gap-2 mb-2">
                    <span class="avatar avatar--lg" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
                    <div>
                        <div class="cell-strong" style="font-size:16px;">{{ $user->name }}</div>
                        <div class="cell-sub">{{ $user->email }}</div>
                        <div style="margin-top:6px;"><x-status-badge type="active" :value="$user->is_active" /></div>
                    </div>
                </div>
                <dl class="dl">
                    <dt>เข้าสู่ระบบล่าสุด</dt><dd>{{ $user->last_login_at?->translatedFormat('j F Y, H:i น.') ?? '—' }}</dd>
                    <dt>พนักงานที่เชื่อมโยง</dt><dd>{{ $user->employee?->full_name ?? '—' }}</dd>
                    <dt>สร้างเมื่อ</dt><dd>{{ $user->created_at?->translatedFormat('j F Y') ?? '—' }}</dd>
                </dl>
            </x-card>

            <x-card title="บทบาท">
                @forelse($user->roles as $role)
                    <div style="margin-bottom:6px;">
                        <x-badge color="blue">{{ $role->name }}</x-badge>
                    </div>
                @empty
                    <x-empty icon="shield" title="ยังไม่ได้กำหนดบทบาท" />
                @endforelse
            </x-card>
        </div>

        <x-card title="สิทธิ์การเข้าถึงที่มีผล" subtitle="รวมจากทุกบทบาทที่กำหนดให้ผู้ใช้งาน">
            @if($user->isSuperAdmin())
                <div style="margin-bottom:12px;">
                    <x-badge color="green">ผู้ดูแลระบบสูงสุด — เข้าถึงได้ทุกสิทธิ์</x-badge>
                </div>
            @endif

            @forelse($grouped as $group => $items)
                <div class="mb-2">
                    <div class="cell-strong" style="margin-bottom:8px;">{{ $groupLabels[$group] ?? $group }}</div>
                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach($items as $permission)
                            <x-badge color="gray">{{ $permission->description ?: $permission->name }}</x-badge>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-empty icon="lock" title="ยังไม่มีสิทธิ์การเข้าถึง" message="ผู้ใช้งานนี้ยังไม่ได้รับสิทธิ์ใด ๆ" />
            @endforelse
        </x-card>
    </div>
@endsection
