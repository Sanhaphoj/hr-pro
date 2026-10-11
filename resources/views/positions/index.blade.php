@extends('layouts.app')
@section('title', 'ตำแหน่ง')

@section('content')
    <x-page-header title="ตำแหน่ง" subtitle="ตำแหน่งงานและระดับภายในองค์กร">
        @can('positions.create')
            <x-slot:actions>
                <x-button href="{{ route('positions.create') }}" icon="plus">เพิ่มตำแหน่ง</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        <div class="toolbar">
            <form method="GET" action="{{ route('positions.index') }}" class="search">
                <x-icon name="search" />
                <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหาชื่อหรือรหัสตำแหน่ง" class="input">
            </form>
        </div>

        @if($positions->isEmpty())
            <x-empty icon="briefcase" title="ยังไม่มีตำแหน่ง" message="เริ่มต้นด้วยการเพิ่มตำแหน่งงานแรก">
                @can('positions.create')
                    <x-button href="{{ route('positions.create') }}" icon="plus">เพิ่มตำแหน่งงาน</x-button>
                @endcan
            </x-empty>
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>ชื่อตำแหน่ง</th>
                            <th>รหัส</th>
                            <th>แผนก</th>
                            <th>ระดับ</th>
                            <th>สถานะ</th>
                            <th class="actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($positions as $position)
                            <tr>
                                <td class="cell-strong">{{ $position->title }}</td>
                                <td class="cell-sub" data-label="รหัส">{{ $position->code }}</td>
                                <td data-label="แผนก">{{ $position->department?->name ?? '—' }}</td>
                                <td data-label="ระดับ">{{ $levelLabels[$position->level] ?? $position->level }}</td>
                                <td data-label="สถานะ"><x-status-badge type="active" :value="$position->is_active" /></td>
                                <td class="actions">
                                    <div class="flex items-center gap-1 justify-end">
                                        @can('positions.update')
                                            <x-button href="{{ route('positions.edit', $position) }}" variant="ghost" size="sm" icon="edit">แก้ไข</x-button>
                                        @endcan
                                        @can('positions.delete')
                                            <form method="POST" action="{{ route('positions.destroy', $position) }}" data-confirm="ยืนยันการลบตำแหน่งนี้?">
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
            <div class="card__pager">{{ $positions->links() }}</div>
        @endif
    </x-card>
@endsection
