@extends('layouts.app')
@section('title', 'บริเวณลงเวลา')

@section('content')
    <x-page-header title="บริเวณลงเวลา" subtitle="กำหนดพื้นที่ที่พนักงานลงเวลาเข้า–ออกงานได้">
        @can('work-locations.create')
            <x-slot:actions>
                <x-button href="{{ route('work-locations.create') }}" icon="plus">เพิ่มบริเวณ</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    @if($locations->isNotEmpty() && ! $enforced)
        <x-alert type="warning" :dismiss="false">
            ขณะนี้ระบบ<strong>ไม่บังคับ</strong>ตรวจสอบตำแหน่ง — พนักงานลงเวลาจากนอกบริเวณได้ แต่ระบบจะบันทึกพิกัดไว้
            ตั้งค่า <code>HRPRO_ATTENDANCE_REQUIRE_LOCATION=true</code> ในไฟล์ <code>.env</code> เพื่อเปิดการบังคับ
        </x-alert>
    @endif

    <x-card :padding="false">
        @if($locations->isEmpty())
            <x-empty icon="map-pin" title="ยังไม่มีบริเวณลงเวลา"
                     message="ตราบใดที่ยังไม่มีบริเวณ พนักงานจะลงเวลาได้จากทุกที่ — เพิ่มบริเวณแรกเพื่อเริ่มจำกัดพื้นที่">
                @can('work-locations.create')
                    <x-button href="{{ route('work-locations.create') }}" icon="plus">เพิ่มบริเวณ</x-button>
                @endcan
            </x-empty>
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>ชื่อบริเวณ</th>
                            <th>รหัส</th>
                            <th>พิกัด</th>
                            <th>รัศมี</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locations as $location)
                            <tr>
                                <td>
                                    <div class="cell-strong">{{ $location->name }}</div>
                                    @if($location->address)
                                        <div class="cell-sub">{{ $location->address }}</div>
                                    @endif
                                </td>
                                <td class="cell-sub" data-label="รหัส">{{ $location->code }}</td>
                                <td class="cell-sub" data-label="พิกัด">
                                    <a href="{{ $location->map_url }}" target="_blank" rel="noopener noreferrer">
                                        {{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}
                                    </a>
                                </td>
                                <td class="cell-strong" data-label="รัศมี">{{ number_format($location->radius_meters) }} ม.</td>
                                <td data-label="สถานะ"><x-status-badge type="active" :value="$location->is_active" /></td>
                                <td class="actions">
                                    <div class="flex items-center gap-2 justify-end">
                                        @can('work-locations.update')
                                            <a href="{{ route('work-locations.edit', $location) }}" class="btn btn--ghost btn--sm" title="แก้ไข">
                                                <x-icon name="edit" width="16" height="16" />
                                            </a>
                                        @endcan
                                        @can('work-locations.delete')
                                            <form method="POST" action="{{ route('work-locations.destroy', $location) }}" data-confirm="ยืนยันการลบบริเวณนี้? ประวัติการลงเวลาเดิมจะยังอยู่แต่จะไม่ผูกกับบริเวณอีก">
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
            <div class="card__pager">{{ $locations->links() }}</div>
        @endif
    </x-card>
@endsection
