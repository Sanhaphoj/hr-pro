@extends('layouts.app')
@section('title', 'แผนก')

@section('content')
    <x-page-header title="แผนก" subtitle="โครงสร้างหน่วยงานภายในองค์กร">
        @can('departments.create')
            <x-slot:actions>
                <x-button href="{{ route('departments.create') }}" icon="plus">เพิ่มแผนก</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        <div class="toolbar">
            <form method="GET" action="{{ route('departments.index') }}" class="search">
                <x-icon name="search" />
                <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหาชื่อหรือรหัสแผนก" class="input">
            </form>
        </div>

        @if($departments->isEmpty())
            <x-empty icon="building" title="ยังไม่มีแผนก" message="เริ่มต้นด้วยการเพิ่มแผนกแรกขององค์กร" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อแผนก</th>
                            <th>หัวหน้าแผนก</th>
                            <th>จำนวนพนักงาน</th>
                            <th>สถานะ</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departments as $department)
                            <tr>
                                <td class="cell-sub">{{ $department->code }}</td>
                                <td class="cell-strong">{{ $department->name }}</td>
                                <td>{{ $department->manager?->full_name ?? '—' }}</td>
                                <td>{{ number_format($department->employees_count) }} คน</td>
                                <td><x-status-badge type="active" :value="$department->is_active" /></td>
                                <td style="text-align:right;">
                                    <div class="flex items-center gap-1" style="justify-content:flex-end;">
                                        @can('departments.update')
                                            <x-button href="{{ route('departments.edit', $department) }}" variant="ghost" size="sm" icon="edit">แก้ไข</x-button>
                                        @endcan
                                        @can('departments.delete')
                                            <form method="POST" action="{{ route('departments.destroy', $department) }}" data-confirm="ยืนยันการลบแผนกนี้?">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="danger" size="sm" icon="trash">ลบ</x-button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $departments->links() }}</div>
        @endif
    </x-card>
@endsection
