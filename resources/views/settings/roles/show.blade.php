@extends('layouts.app')
@section('title', 'รายละเอียดบทบาท')

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
@endphp

@section('content')
    <x-page-header title="รายละเอียดบทบาท" subtitle="{{ $role->name }}">
        <x-slot:actions>
            @can('roles.update')
                @unless($role->is_system)
                    <x-button :href="route('settings.roles.edit', $role)" variant="secondary" icon="edit">แก้ไข</x-button>
                @endunless
            @endcan
            <x-button :href="route('settings.roles.index')" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid--2">
        <x-card title="สิทธิ์การเข้าถึงที่ได้รับ" subtitle="สิทธิ์ทั้งหมดที่บทบาทนี้สามารถใช้งานได้">
            @php
                $assigned = $role->permissions->groupBy('group');
            @endphp
            @forelse($permissionGroups as $group => $permissions)
                @php $items = $assigned->get($group); @endphp
                @if($items && $items->isNotEmpty())
                    <div style="margin-bottom:16px;">
                        <div class="cell-strong" style="margin-bottom:8px;">{{ $groupLabels[$group] ?? $group }}</div>
                        <div class="flex items-center gap-2" style="flex-wrap:wrap;">
                            @foreach($items as $permission)
                                <x-badge color="blue">{{ $permission->description ?: $permission->name }}</x-badge>
                            @endforeach
                        </div>
                    </div>
                @endif
            @empty
            @endforelse

            @if($role->permissions->isEmpty())
                <x-empty icon="lock" title="ยังไม่ได้กำหนดสิทธิ์" message="บทบาทนี้ยังไม่มีสิทธิ์การเข้าถึงใด ๆ" />
            @endif
        </x-card>

        <div class="grid" style="align-content:start;">
            <x-card title="ข้อมูลบทบาท">
                <dl class="dl">
                    <dt>ชื่อบทบาท</dt><dd>{{ $role->name }}</dd>
                    <dt>รหัส (slug)</dt><dd><code>{{ $role->slug }}</code></dd>
                    <dt>คำอธิบาย</dt><dd>{{ $role->description ?: '—' }}</dd>
                    <dt>ประเภท</dt>
                    <dd>
                        @if($role->is_system)
                            <x-badge color="amber">ระบบ</x-badge>
                        @else
                            <x-badge color="gray">กำหนดเอง</x-badge>
                        @endif
                    </dd>
                    <dt>จำนวนสิทธิ์</dt><dd>{{ number_format($role->permissions->count()) }} สิทธิ์</dd>
                </dl>
            </x-card>

            <x-card title="ผู้ใช้งานที่มีบทบาทนี้">
                @forelse($role->users as $user)
                    <div class="flex items-center gap-2" style="margin-bottom:10px;">
                        <span class="avatar" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
                        <div style="flex:1;">
                            <div class="cell-strong">{{ $user->name }}</div>
                            <div class="cell-sub">{{ $user->email }}</div>
                        </div>
                        @can('users.view')
                            <a href="{{ route('settings.users.show', $user) }}" class="btn btn--ghost btn--sm">ดู</a>
                        @endcan
                    </div>
                @empty
                    <x-empty icon="users" title="ยังไม่มีผู้ใช้งาน" message="ยังไม่มีผู้ใช้งานที่ได้รับบทบาทนี้" />
                @endforelse
            </x-card>
        </div>
    </div>
@endsection
