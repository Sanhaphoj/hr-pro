@extends('layouts.app')
@section('title', 'ลงเวลาทำงาน')

@section('content')
    <x-page-header title="ลงเวลาทำงาน" subtitle="ลงเวลาเข้า–ออกงาน และดูประวัติการลงเวลา" />

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
                <div class="grid grid--halves" style="align-items:center;">
                    <div>
                        <div class="cell-sub" style="margin-bottom:4px;">เวลาปัจจุบัน</div>
                        <div id="live-clock" style="font-size:34px;font-weight:700;letter-spacing:1px;">--:--:--</div>
                        <div style="margin-top:14px;">
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
                        </dl>
                    </div>
                </div>

                <div class="toolbar" style="margin-top:18px;">
                    <form method="POST" action="{{ route('attendance.clock-in') }}">
                        @csrf
                        <x-button type="submit" variant="success" icon="clock" :disabled="$hasClockIn ? 'disabled' : null">ลงเวลาเข้างาน</x-button>
                    </form>
                    <form method="POST" action="{{ route('attendance.clock-out') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="log-out" :disabled="(! $hasClockIn || $hasClockOut) ? 'disabled' : null">ลงเวลาออกงาน</x-button>
                    </form>
                </div>
            </x-card>
        @endif

        <div style="margin-top:18px;">
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
                        <table class="table">
                            <thead>
                                <tr>
                                    @if($canViewAll)<th>พนักงาน</th>@endif
                                    <th>วันที่</th>
                                    <th>เวลาเข้า</th>
                                    <th>เวลาออก</th>
                                    <th>ชั่วโมงทำงาน</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $record)
                                    <tr>
                                        @if($canViewAll)
                                            <td class="cell-strong">{{ $record->employee?->full_name ?? '—' }}</td>
                                        @endif
                                        <td>{{ $record->work_date->format('d/m/Y') }}</td>
                                        <td>{{ $record->clock_in ? $record->clock_in->format('H:i') : '—' }}</td>
                                        <td>{{ $record->clock_out ? $record->clock_out->format('H:i') : '—' }}</td>
                                        <td class="cell-sub">{{ $record->clock_out ? number_format($record->worked_hours, 2) . ' ชม.' : '—' }}</td>
                                        <td><x-status-badge type="attendance" :value="$record->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:14px;">
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
