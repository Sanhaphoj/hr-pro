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
            <x-empty icon="building" title="ยังไม่มีแผนก" message="เริ่มต้นด้วยการเพิ่มแผนกแรกขององค์กร">
                @can('departments.create')
                    <x-button href="{{ route('departments.create') }}" icon="plus">เพิ่มแผนก</x-button>
                @endcan
            </x-empty>
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>ชื่อแผนก</th>
                            <th>รหัส</th>
                            <th>หัวหน้าแผนก</th>
                            <th>จำนวนพนักงาน</th>
                            <th>สถานะ</th>
                            <th class="actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departments as $department)
                            <tr>
                                <td class="cell-strong">{{ $department->name }}</td>
                                <td class="cell-sub" data-label="รหัส">{{ $department->code }}</td>
                                <td data-label="หัวหน้าแผนก">{{ $department->manager?->full_name ?? '—' }}</td>
                                <td data-label="จำนวนพนักงาน">{{ number_format($department->employees_count) }} คน</td>
                                <td data-label="สถานะ"><x-status-badge type="active" :value="$department->is_active" /></td>
                                <td class="actions">
                                    <div class="flex items-center gap-1 justify-end">
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
            <div class="card__pager">{{ $departments->links() }}</div>
        @endif
    </x-card>
@endsection
