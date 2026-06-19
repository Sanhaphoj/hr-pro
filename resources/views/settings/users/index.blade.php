@extends('layouts.app')
@section('title', 'ผู้ใช้งานระบบ')

@section('content')
    <x-page-header title="ผู้ใช้งานระบบ" subtitle="จัดการบัญชีผู้ใช้งานและสิทธิ์การเข้าถึงระบบ">
        @can('users.create')
            <x-slot:actions>
                <x-button :href="route('settings.users.create')" icon="plus">เพิ่มผู้ใช้งาน</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <form method="GET" action="{{ route('settings.users.index') }}" class="toolbar">
        <div class="search">
            <x-icon name="search" />
            <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหาด้วยชื่อหรืออีเมล" class="input">
        </div>
        <x-button type="submit" variant="secondary" icon="search">ค้นหา</x-button>
        @if($search !== '')
            <a href="{{ route('settings.users.index') }}" class="btn btn--ghost">ล้างตัวกรอง</a>
        @endif
    </form>

    <x-card :padding="false">
        @if($users->isEmpty())
            <x-empty icon="users" title="ไม่พบผู้ใช้งาน" message="ยังไม่มีบัญชีผู้ใช้งานที่ตรงกับเงื่อนไข" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ผู้ใช้งาน</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                            <th>เข้าสู่ระบบล่าสุด</th>
                            <th style="text-align:right;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="avatar" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
                                        <div>
                                            <div class="cell-strong">{{ $user->name }}</div>
                                            <div class="cell-sub">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <x-badge color="blue">{{ $role->name }}</x-badge>
                                    @empty
                                        <span class="cell-sub">—</span>
                                    @endforelse
                                </td>
                                <td><x-status-badge type="active" :value="$user->is_active" /></td>
                                <td class="cell-sub">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                                <td style="text-align:right;">
                                    <div class="flex items-center gap-2" style="justify-content:flex-end;">
                                        @can('users.view')
                                            <x-button :href="route('settings.users.show', $user)" variant="ghost" size="sm" icon="eye">ดู</x-button>
                                        @endcan
                                        @can('users.update')
                                            <x-button :href="route('settings.users.edit', $user)" variant="ghost" size="sm" icon="edit">แก้ไข</x-button>
                                        @endcan
                                        @can('users.delete')
                                            @if($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('settings.users.destroy', $user) }}" data-confirm="ยืนยันการลบผู้ใช้งานนี้?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-button type="submit" variant="danger" size="sm" icon="trash">ลบ</x-button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 20px;">{{ $users->links() }}</div>
        @endif
    </x-card>
@endsection
