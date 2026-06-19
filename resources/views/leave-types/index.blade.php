@extends('layouts.app')
@section('title', 'ประเภทการลา')

@section('content')
    <x-page-header title="ประเภทการลา" subtitle="จัดการหมวดหมู่การลาและสิทธิ์วันลาต่อปี">
        @can('leave-types.create')
            <x-slot:actions>
                <x-button href="{{ route('leave-types.create') }}" icon="plus">เพิ่มประเภทการลา</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        @if($leaveTypes->isEmpty())
            <x-empty icon="calendar" title="ยังไม่มีประเภทการลา" message="เริ่มต้นด้วยการเพิ่มประเภทการลาประเภทแรก" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ชื่อประเภท</th>
                            <th>รหัส</th>
                            <th>วันลาต่อปี</th>
                            <th>ค่าจ้าง</th>
                            <th>ต้องอนุมัติ</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaveTypes as $leaveType)
                            <tr>
                                <td>
                                    <span class="flex items-center gap-2">
                                        <x-badge :color="$leaveType->color" dot>{{ $leaveType->name }}</x-badge>
                                    </span>
                                    @if($leaveType->description)
                                        <div class="cell-sub">{{ \Illuminate\Support\Str::limit($leaveType->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="cell-sub">{{ $leaveType->code }}</td>
                                <td class="cell-strong">{{ number_format($leaveType->days_per_year) }} วัน</td>
                                <td>
                                    @if($leaveType->is_paid)
                                        <x-badge color="green">มีค่าจ้าง</x-badge>
                                    @else
                                        <x-badge color="gray">ไม่มีค่าจ้าง</x-badge>
                                    @endif
                                </td>
                                <td class="cell-sub">{{ $leaveType->requires_approval ? 'ใช่' : 'ไม่' }}</td>
                                <td><x-status-badge type="active" :value="$leaveType->is_active" /></td>
                                <td>
                                    <div class="flex items-center gap-2 justify-end">
                                        @can('leave-types.update')
                                            <a href="{{ route('leave-types.edit', $leaveType) }}" class="btn btn--ghost btn--sm" title="แก้ไข">
                                                <x-icon name="edit" width="16" height="16" />
                                            </a>
                                        @endcan
                                        @can('leave-types.delete')
                                            <form method="POST" action="{{ route('leave-types.destroy', $leaveType) }}" data-confirm="ยืนยันการลบประเภทการลานี้?">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="danger" size="sm" icon="trash" title="ลบ" />
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $leaveTypes->links() }}</div>
        @endif
    </x-card>
@endsection
