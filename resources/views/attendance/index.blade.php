@extends('layouts.app')
@section('title', 'ประวัติการลงเวลา')

@section('content')
    <x-page-header title="ประวัติการลงเวลา" subtitle="สรุปการลงเวลาเข้า–ออกงานและสถานที่ที่ลงเวลา">
        <x-slot:actions>
            @if($canViewAll)
                <x-button :href="route('attendance.print', ['date' => request('date') ?: now()->toDateString()])" variant="secondary" icon="printer">พิมพ์รายงานประจำวัน</x-button>
            @endif
            @if($employee)
                <x-button :href="route('attendance.print')" variant="secondary" icon="file-text">ใบลงเวลาของฉัน</x-button>
            @endif
            <x-button href="{{ route('time-clock.show') }}" icon="map-pin">ไปหน้าลงเวลา</x-button>
        </x-slot:actions>
    </x-page-header>

    @if(! $employee && ! $canViewAll)
        <x-card>
            <x-empty icon="clock" title="ยังไม่มีโปรไฟล์พนักงาน" message="บัญชีของคุณยังไม่ได้เชื่อมโยงกับข้อมูลพนักงาน กรุณาติดต่อฝ่ายบุคคล" />
        </x-card>
    @else
        @if($employee)
            @php
                $hasClockIn = $today && $today->clock_in;
                $hasClockOut = $today && $today->clock_out;
            @endphp
            <x-card title="ลงเวลาวันนี้" subtitle="{{ now()->translatedFormat('j F Y') }}">
                <x-slot:actions>
                    <x-button href="{{ route('time-clock.show') }}" variant="ghost" size="sm" icon="crosshair">เปิดหน้าลงเวลา</x-button>
                </x-slot:actions>

                <div class="grid grid--halves" style="align-items:center;">
                    <div>
                        <div class="cell-sub" style="margin-bottom:4px;">เวลาปัจจุบัน</div>
                        <div id="live-clock" style="font-size:34px;font-weight:700;letter-spacing:1px;">--:--:--</div>
                        <div class="field-gap">
                            @if($today)
                                <x-status-badge type="attendance" :value="$today->status" />
                            @else
                                <span class="cell-sub">ยังไม่ได้ลงเวลาในวันนี้</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <dl class="dl">
                            <dt>เวลาเข้างาน</dt>
                            <dd>{{ $hasClockIn ? $today->clock_in->format('H:i') . ' น.' : '—' }}</dd>
                            <dt>เวลาออกงาน</dt>
                            <dd>{{ $hasClockOut ? $today->clock_out->format('H:i') . ' น.' : '—' }}</dd>
                            <dt>ชั่วโมงทำงาน</dt>
                            <dd>{{ $hasClockOut ? number_format($today->worked_hours, 2) . ' ชม.' : '—' }}</dd>
                            <dt>สถานที่</dt>
                            <dd>{{ $today?->clockInLocation?->name ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </x-card>
        @endif

        <div class="section-gap">
            <x-card title="{{ $canViewAll ? 'ประวัติการลงเวลาทั้งหมด' : 'ประวัติการลงเวลาของฉัน' }}"
                    subtitle="{{ $canViewAll ? 'รายการลงเวลาของพนักงานทุกคน' : 'ย้อนหลัง 30 วันล่าสุด' }}">
                @if($canViewAll)
                    <x-slot:actions>
                        <form method="GET" action="{{ route('attendance.index') }}" class="toolbar">
                            <x-input name="date" type="date" :value="request('date')" />
                            <x-button type="submit" variant="ghost" size="sm" icon="filter">กรอง</x-button>
                            @if(request('date'))
                                <x-button href="{{ route('attendance.index') }}" variant="ghost" size="sm">ล้าง</x-button>
                            @endif
                        </form>
                    </x-slot:actions>
                @endif

                @if(! $records || $records->isEmpty())
                    <x-empty icon="clock" title="ยังไม่มีประวัติการลงเวลา" message="เมื่อมีการลงเวลา รายการจะปรากฏที่นี่" />
                @else
                    <div class="table-wrap">
                        <table class="table table--cards">
                            <thead>
                                <tr>
                                    @if($canViewAll)<th>พนักงาน</th>@endif
                                    <th>วันที่</th>
                                    <th>เวลาเข้า</th>
                                    <th>เวลาออก</th>
                                    <th>ชั่วโมงทำงาน</th>
                                    <th>สถานที่</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $record)
                                    <tr>
                                        @if($canViewAll)
                                            <td class="cell-strong">{{ $record->employee?->full_name ?? '—' }}</td>
                                        @endif
                                        <td data-label="วันที่">{{ $record->work_date->format('d/m/Y') }}</td>
                                        <td data-label="เวลาเข้า">
                                            {{ $record->clock_in ? $record->clock_in->format('H:i') : '—' }}
                                            @if($record->clock_in_photo)
                                                <a href="{{ route('attendance.photo', [$record, 'in']) }}" target="_blank" class="photo-link" title="ภาพยืนยันการเข้างาน"><x-icon name="camera" width="15" height="15" /></a>
                                            @endif
                                            @if($record->late_reason)
                                                <div class="cell-sub" title="เหตุผลที่มาสาย">“{{ $record->late_reason }}”</div>
                                            @endif
                                        </td>
                                        <td data-label="เวลาออก">
                                            {{ $record->clock_out ? $record->clock_out->format('H:i') : '—' }}
                                            @if($record->clock_out_photo)
                                                <a href="{{ route('attendance.photo', [$record, 'out']) }}" target="_blank" class="photo-link" title="ภาพยืนยันการออกงาน"><x-icon name="camera" width="15" height="15" /></a>
                                            @endif
                                            @if($record->left_early)
                                                <div class="cell-sub" title="{{ $record->early_leave_reason ?? 'ไม่ได้ระบุเหตุผล' }}">
                                                    ออกก่อนเวลา{{ $record->early_leave_reason ? ' — “'.$record->early_leave_reason.'”' : '' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="cell-sub" data-label="ชั่วโมงทำงาน">{{ $record->clock_out ? number_format($record->worked_hours, 2) . ' ชม.' : '—' }}</td>
                                        <td data-label="สถานที่">
                                            @if($record->clockInLocation)
                                                <x-badge color="blue" dot>{{ $record->clockInLocation->name }}</x-badge>
                                            @elseif($record->clock_in_off_site)
                                                {{-- Outside every approved zone; the reason is optional. --}}
                                                <x-badge color="amber" dot>นอกสถานที่</x-badge>
                                                @if($record->clock_in_off_site_reason)
                                                    <div class="cell-sub" title="{{ $record->clock_in_latitude }}, {{ $record->clock_in_longitude }}">“{{ $record->clock_in_off_site_reason }}”</div>
                                                @endif
                                            @else
                                                <span class="cell-sub">—</span>
                                            @endif
                                        </td>
                                        <td data-label="สถานะ"><x-status-badge type="attendance" :value="$record->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="field-gap">
                        {{ $records->links() }}
                    </div>
                @endif
            </x-card>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        var el = document.getElementById('live-clock');
        if (!el) return;
        function tick() {
            var now = new Date();
            var h = String(now.getHours()).padStart(2, '0');
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            el.textContent = h + ':' + m + ':' + s;
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endpush
