@extends('layouts.print')

@php
    $labels = ['present' => 'มาทำงาน', 'late' => 'มาสาย', 'absent' => 'ขาดงาน', 'half_day' => 'ครึ่งวัน', 'on_leave' => 'ลางาน'];
    $clock = fn (int $minutes) => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    $dayLabel = 'วัน'.thai_day($date->dayOfWeek).'ที่ '.thai_date($date);
@endphp

@section('orientation', 'landscape')
@section('title', 'รายงานการลงเวลาประจำวัน · '.thai_date($date))
@section('filename', sprintf('รายงานลงเวลาประจำวัน_%d-%02d-%02d', $date->year + 543, $date->month, $date->day))
@section('back', route('attendance.index', ['date' => $date->toDateString()]))

@section('options')
    <form method="GET" action="{{ route('attendance.print') }}" class="doc-options">
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="doc-field" aria-label="วันที่">
        <button type="submit" class="doc-btn">แสดง</button>
    </form>
@endsection

@section('content')
    <article class="sheet sheet--landscape">
        <x-doc.letterhead title="รายงานการลงเวลาประจำวัน" subtitle="Daily attendance"
                          :meta="['ประจำ' => $dayLabel]" />

        @unless($working)
            <p class="doc-filter">{{ $date->isToday() ? 'วันนี้' : 'วันที่เลือก' }}ไม่ใช่วันทำงานตามปฏิทินบริษัท</p>
        @endunless

        <div class="doc-stats">
            <div class="doc-stat"><span>พนักงาน</span><b>{{ $summary['employees'] }} <small>คน</small></b></div>
            <div class="doc-stat"><span>ลงเวลาเข้างาน</span><b>{{ $summary['present'] }}</b></div>
            <div class="doc-stat"><span>มาสาย</span><b>{{ $summary['late'] }}</b></div>
            <div class="doc-stat"><span>ออกก่อนเวลา</span><b>{{ $summary['early'] }}</b></div>
            <div class="doc-stat"><span>ลางาน</span><b>{{ $summary['leave'] }}</b></div>
            <div class="doc-stat"><span>{{ $isPast ? 'ไม่มีบันทึก' : 'ยังไม่ลงเวลา' }}</span><b>{{ $summary['missing'] }}</b></div>
            <div class="doc-stat"><span>นอกสถานที่</span><b>{{ $summary['off_site'] }}</b></div>
        </div>

        @if($rows->isEmpty())
            <div class="doc-empty">ไม่มีพนักงานในระบบ</div>
        @else
            <div class="doc-table-wrap">
                <table class="doc-table doc-table--compact doc-table--dense">
                    <thead>
                        <tr>
                            <th class="center">ลำดับ</th>
                            <th>รหัส</th>
                            <th>ชื่อ–สกุล</th>
                            <th>แผนก</th>
                            <th class="center">เข้างาน</th>
                            <th class="center">ออกงาน</th>
                            <th class="num">ชั่วโมง</th>
                            <th>สถานะ</th>
                            <th>สถานที่</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows->values() as $i => $row)
                            @php
                                $record = $row['record'];
                                $status = match (true) {
                                    $record !== null => $labels[$record->status] ?? $record->status,
                                    $row['leave'] !== null => 'ลางาน',
                                    $row['missing'] => $isPast ? 'ไม่มีบันทึก' : 'ยังไม่ลงเวลา',
                                    default => '',
                                };
                                $notes = array_filter([
                                    $row['leave'] ? ($row['leave']->leaveType?->name ?? 'ลา') : null,
                                    $record?->late_reason ? 'สาย: '.$record->late_reason : null,
                                    $record?->left_early ? 'ออกก่อนเวลา'.($record->early_leave_reason ? ': '.$record->early_leave_reason : '') : null,
                                    $record?->clock_in_off_site_reason ? 'เข้างานนอกสถานที่: '.$record->clock_in_off_site_reason : null,
                                    $record?->clock_out_off_site_reason ? 'ออกงานนอกสถานที่: '.$record->clock_out_off_site_reason : null,
                                ]);
                            @endphp
                            <tr @class(['is-alert' => $row['missing'] && $isPast])>
                                <td class="center">{{ $i + 1 }}</td>
                                <td class="nowrap">{{ $row['employee']->employee_code }}</td>
                                <td>{{ $row['employee']->full_name }}</td>
                                <td>{{ $row['employee']->department?->name ?? '—' }}</td>
                                <td class="center">{{ $record?->clock_in?->format('H:i') }}</td>
                                <td class="center">{{ $record?->clock_out?->format('H:i') }}</td>
                                <td class="num">{{ $record?->clock_out ? $clock((int) $record->worked_minutes) : '' }}</td>
                                <td class="nowrap"><span class="doc-status">{{ $status }}</span></td>
                                <td>{{ $record?->clockInLocation?->name ?? ($record?->clock_in_off_site ? 'นอกสถานที่' : '') }}</td>
                                <td class="doc-table__note">{{ implode(' · ', $notes) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="signs" style="--cols: 2">
            <x-doc.signature role="ผู้จัดทำรายงาน" />
            <x-doc.signature role="ผู้ตรวจสอบ" />
        </div>

        <x-doc.footer />
    </article>
@endsection
