@extends('layouts.app')
@section('title', 'บทบาทและสิทธิ์')

@section('content')
    <x-page-header title="บทบาทและสิทธิ์" subtitle="กำหนดบทบาทและสิทธิ์การเข้าถึงสำหรับผู้ใช้งาน">
        @can('roles.create')
            <x-slot:actions>
                <x-button :href="route('settings.roles.create')" icon="plus">เพิ่มบทบาท</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        @if($roles->isEmpty())
            <x-empty icon="shield" title="ยังไม่มีบทบาท" message="สร้างบทบาทเพื่อกำหนดสิทธิ์การเข้าถึงระบบ" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ชื่อบทบาท</th>
                            <th>รหัส (slug)</th>
                            <th>คำอธิบาย</th>
                            <th>สิทธิ์</th>
                            <th>ผู้ใช้งาน</th>
                            <th>ประเภท</th>
                            <th style="text-align:right;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td class="cell-strong">{{ $role->name }}</td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td class="cell-sub">{{ $role->description ?: '—' }}</td>
                                <td>{{ number_format($role->permissions_count) }} สิทธิ์</td>
                                <td>{{ number_format($role->users_count) }} คน</td>
                                <td>
                                    @if($role->is_system)
                                        <x-badge color="amber">ระบบ</x-badge>
                                    @else
                                        <x-badge color="gray">กำหนดเอง</x-badge>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    <div class="flex items-center gap-2" style="justify-content:flex-end;">
                                        @can('roles.view')
                                            <x-button :href="route('settings.roles.show', $role)" variant="ghost" size="sm" icon="eye">ดู</x-button>
                                        @endcan
                                        @if($role->is_system)
                                            <span class="cell-sub flex items-center gap-2"><x-icon name="lock" width="14" height="14" /> บทบาทของระบบ</span>
                                        @else
                                            @can('roles.update')
                                                <x-button :href="route('settings.roles.edit', $role)" variant="ghost" size="sm" icon="edit">แก้ไข</x-button>
                                            @endcan
                                            @can('roles.delete')
                                                <form method="POST" action="{{ route('settings.roles.destroy', $role) }}" data-confirm="ยืนยันการลบบทบาทนี้?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-button type="submit" variant="danger" size="sm" icon="trash">ลบ</x-button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 20px;">{{ $roles->links() }}</div>
        @endif
    </x-card>
@endsection
