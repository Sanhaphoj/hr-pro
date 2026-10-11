@extends('layouts.app')
@section('title', 'พนักงาน')

@section('content')
    <x-page-header title="พนักงาน" subtitle="ทำเนียบและข้อมูลพนักงานทั้งหมดในองค์กร">
        <x-slot:actions>
            <x-button :href="route('employees.print-list', array_filter($filters))" variant="secondary" icon="printer">พิมพ์รายชื่อ</x-button>
            @can('employees.create')
                <x-button href="{{ route('employees.create') }}" icon="plus">เพิ่มพนักงาน</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <form method="GET" action="{{ route('employees.index') }}" class="toolbar" style="padding:16px 20px;">
            <div class="search">
                <x-icon name="search" width="18" height="18" />
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="ค้นหาชื่อ รหัส หรืออีเมล" class="input">
            </div>
            <select name="department_id" class="select" style="max-width:200px;">
                <option value="">ทุกแผนก</option>
                @foreach($departments as $id => $name)
                    <option value="{{ $id }}" @selected((string) $filters['department_id'] === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="status" class="select" style="max-width:180px;">
                <option value="">ทุกสถานะ</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected((string) $filters['status'] === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary" icon="filter">กรอง</x-button>
        </form>

        @if($employees->isEmpty())
            <x-empty icon="users" title="ไม่พบพนักงาน" message="ลองล้างตัวกรอง หรือเริ่มต้นด้วยการเพิ่มพนักงานคนแรก">
                @can('employees.create')
                    <x-button href="{{ route('employees.create') }}" icon="plus">เพิ่มพนักงาน</x-button>
                @endcan
            </x-empty>
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>พนักงาน</th>
                            <th>รหัสพนักงาน</th>
                            <th>แผนก</th>
                            <th>ตำแหน่ง</th>
                            <th>สถานะ</th>
                            <th class="actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="avatar" style="background: {{ avatar_color($employee->email) }}">{{ $employee->initials }}</span>
                                        <div>
                                            <a href="{{ route('employees.show', $employee) }}" class="cell-strong">{{ $employee->full_name }}</a>
                                            <div class="cell-sub">{{ $employee->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="รหัสพนักงาน">{{ $employee->employee_code }}</td>
                                <td data-label="แผนก">{{ $employee->department?->name ?? '—' }}</td>
                                <td data-label="ตำแหน่ง">{{ $employee->position?->title ?? '—' }}</td>
                                <td data-label="สถานะ"><x-status-badge type="employee" :value="$employee->status" /></td>
                                <td class="actions">
                                    <div class="flex items-center gap-1 justify-end">
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn--ghost btn--sm" title="ดู"><x-icon name="eye" width="16" height="16" /></a>
                                        @can('employees.update')
                                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn--ghost btn--sm" title="แก้ไข"><x-icon name="edit" width="16" height="16" /></a>
                                        @endcan
                                        @can('employees.delete')
                                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" data-confirm="ยืนยันการลบ?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn--ghost btn--sm" title="ลบ"><x-icon name="trash" width="16" height="16" /></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card__pager">{{ $employees->links() }}</div>
        @endif
    </x-card>
@endsection
