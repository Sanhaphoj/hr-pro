@extends('layouts.app')
@section('title', 'ตั้งค่า')

@php
    $user = auth()->user();
    $shortcuts = array_filter([
        ['users.view', 'settings.users.index', 'user', 'ผู้ใช้งานระบบ', 'บัญชีผู้ใช้ การเปิด–ปิดใช้งาน และบทบาทของแต่ละคน'],
        ['roles.view', 'settings.roles.index', 'shield', 'บทบาท & สิทธิ์', 'สิทธิ์ของแต่ละบทบาท รวมถึงการบังคับถ่ายภาพลงเวลา'],
        ['work-locations.view', 'work-locations.index', 'target', 'บริเวณลงเวลา', 'จุดและรัศมีที่อนุญาตให้ลงเวลา'],
        ['leave-types.view', 'leave-types.index', 'list', 'ประเภทการลา', 'สิทธิ์วันลาต่อปี และการลาแบบรับ/ไม่รับค่าจ้าง'],
        ['departments.view', 'departments.index', 'building', 'แผนก', 'โครงสร้างหน่วยงานในองค์กร'],
        ['positions.view', 'positions.index', 'briefcase', 'ตำแหน่งงาน', 'ตำแหน่งที่ใช้กับข้อมูลพนักงาน'],
        ['audit-logs.view', 'audit-logs.index', 'history', 'บันทึกการใช้งาน', 'ตรวจสอบว่าใครเปลี่ยนการตั้งค่าอะไร เมื่อไร'],
    ], fn (array $item) => $user->hasPermission($item[0]));
@endphp

@section('content')
    <x-page-header title="ตั้งค่า" subtitle="ปรับการทำงานของระบบให้ตรงกับองค์กร แต่ละหมวดเปิดให้ตามสิทธิ์ของบทบาท" />

    @if($groups)
        <section class="settings-hub">
            <h2 class="settings-hub__title">ตั้งค่าระบบ</h2>
            <div class="settings-hub__grid">
                @foreach($groups as $key => $group)
                    <a href="{{ route('settings.system.edit', $key) }}" class="settings-tile">
                        <span class="settings-tile__icon"><x-icon :name="$group['icon']" /></span>
                        <span class="settings-tile__text">
                            <b>{{ $group['label'] }}</b>
                            <small>{{ $group['description'] }}</small>
                        </span>
                        @if($counts[$key] > 0)
                            <span class="settings-tile__badge" title="ค่าที่ต่างจากค่าเริ่มต้นของระบบ">กำหนดเอง {{ $counts[$key] }}</span>
                        @endif
                        <x-icon name="chevron-right" class="settings-tile__go" />
                    </a>
                @endforeach
            </div>
        </section>
    @else
        <x-alert type="info" :dismiss="false">บัญชีของคุณตั้งค่าได้เฉพาะส่วนตัว หากต้องการเปลี่ยนการตั้งค่าของระบบ กรุณาติดต่อผู้ดูแลระบบ</x-alert>
    @endif

    <section class="settings-hub">
        <h2 class="settings-hub__title">บัญชีของฉัน</h2>
        <div class="settings-hub__grid">
            <a href="{{ route('settings.appearance.edit') }}" class="settings-tile">
                <span class="settings-tile__icon"><x-icon name="palette" /></span>
                <span class="settings-tile__text"><b>การแสดงผล</b><small>ธีมสีและโหมดสว่าง–มืดของบัญชีคุณ</small></span>
                <x-icon name="chevron-right" class="settings-tile__go" />
            </a>
            <a href="{{ route('profile.edit') }}" class="settings-tile">
                <span class="settings-tile__icon"><x-icon name="lock" /></span>
                <span class="settings-tile__text"><b>โปรไฟล์และรหัสผ่าน</b><small>ชื่อ อีเมล และการเปลี่ยนรหัสผ่าน</small></span>
                <x-icon name="chevron-right" class="settings-tile__go" />
            </a>
        </div>
    </section>

    @if($shortcuts)
        <section class="settings-hub">
            <h2 class="settings-hub__title">ข้อมูลหลักและสิทธิ์</h2>
            <div class="settings-hub__grid">
                @foreach($shortcuts as [$permission, $route, $icon, $label, $note])
                    <a href="{{ route($route) }}" class="settings-tile">
                        <span class="settings-tile__icon settings-tile__icon--quiet"><x-icon :name="$icon" /></span>
                        <span class="settings-tile__text"><b>{{ $label }}</b><small>{{ $note }}</small></span>
                        <x-icon name="chevron-right" class="settings-tile__go" />
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
