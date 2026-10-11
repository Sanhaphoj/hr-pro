@extends('layouts.print')

@php
    $period = thai_month($month->month).' '.($month->year + 543);
    $labels = ['present' => 'มาทำงาน', 'late' => 'มาสาย', 'absent' => 'ขาดงาน', 'half_day' => 'ครึ่งวัน', 'on_leave' => 'ลางาน'];
    $clock = fn (int $minutes) => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
@endphp

@section('title', 'ใบลงเวลาทำงาน · '.$employee->full_name.' · '.$period)
@section('filename', sprintf('ใบลงเวลา_%s_%d-%02d', $employee->employee_code, $month->year + 543, $month->month))
@section('back', route('attendance.index'))

@section('options')
    <form method="GET" action="{{ route('attendance.print') }}" class="doc-options">
        @if($canViewAll)
            <select name="employee_id" class="doc-field" aria-label="พนักงาน">
                @foreach($employees as $option)
                    <option value="{{ $option->id }}" @selected($option->id === $employee->id)>{{ $option->full_name }}</option>
                @endforeach
            </select>
        @endif
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="doc-field" aria-label="เดือน">
        <button type="submit" class="doc-btn">แสดง</button>
        @if($canViewAll)
            <a href="{{ route('attendance.print', ['date' => now()->toDateString()]) }}" class="doc-btn doc-btn--ghost">รายงานประจำวัน</a>
        @endif
    </form>
@endsection

@section('content')
    <article class="sheet">
        <x-doc.letterhead title="ใบลงเวลาทำงาน" subtitle="Monthly timesheet"
                          :meta="['เลขที่' => doc_no('TS', $employee->id, $month), 'ประจำเดือน' => $period]" />

        <dl class="kv kv--3">
            <div class="kv__row"><dt>ชื่อ–สกุล</dt><dd>{{ $employee->full_name }}</dd></div>
            <div class="kv__row"><dt>รหัส</dt><dd>{{ $employee->employee_code }}</dd></div>
            <div class="kv__row"><dt>แผนก</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div>
            <div class="kv__row"><dt>ตำแหน่ง</dt><dd>{{ $employee->position?->title ?? '—' }}</dd></div>
            <div class="kv__row"><dt>เวลางาน</dt><dd>{{ config('hrpro.work.start') }}–{{ config('hrpro.work.end') }} น.</dd></div>
            <div class="kv__row"><dt>หัวหน้างาน</dt><dd>{{ $employee->manager?->full_name ?? '—' }}</dd></div>
        </dl>

        <div class="doc-stats doc-stats--dense" style="margin-top: 3mm;">
            <div class="doc-stat"><span>วันทำงาน</span><b>{{ $summary['working'] }}</b></div>
            <div class="doc-stat"><span>มาทำงาน</span><b>{{ $summary['present'] }}</b></div>
            <div class="doc-stat"><span>มาสาย</span><b>{{ $summary['late'] }}</b></div>
            <div class="doc-stat"><span>ออกก่อนเวลา</span><b>{{ $summary['early'] }}</b></div>
            <div class="doc-stat"><span>ลางาน</span><b>{{ $summary['leave'] }}</b></div>
            <div class="doc-stat"><span>ไม่มีบันทึก</span><b>{{ $summary['missing'] }}</b></div>
            <div class="doc-stat"><span>นอกสถานที่</span><b>{{ $summary['off_site'] }}</b></div>
            <div class="doc-stat"><span>ชั่วโมงรวม</span><b>{{ $clock($summary['minutes']) }}</b></div>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table doc-table--compact doc-table--dense">
                <thead>
                    <tr>
                        <th class="center">วันที่</th>
                        <th>วัน</th>
                        <th class="center">เข้างาน</th>
                        <th class="center">ออกงาน</th>
                        <th class="num">ชั่วโมง</th>
                        <th>สถานะ</th>
                        <th>สถานที่</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day)
                        @php
                            $record = $day['record'];
                            $status = match (true) {
                                $record !== null => $labels[$record->status] ?? $record->status,
                                $day['leave'] !== null && $day['working'] => 'ลางาน',
                                ! $day['working'] => 'วันหยุด',
                                $day['missing'] => 'ไม่มีบันทึก',
                                default => '',
                            };
                            $notes = array_filter([
                                $day['leave'] ? ($day['leave']->leaveType?->name ?? 'ลา') : null,
                                $record?->late_reason ? 'สาย: '.$record->late_reason : null,
                                $record?->left_early ? 'ออกก่อนเวลา'.($record->early_leave_reason ? ': '.$record->early_leave_reason : '') : null,
                                $record?->clock_in_off_site_reason ? 'นอกสถานที่: '.$record->clock_in_off_site_reason : null,
                            ]);
                        @endphp
                        <tr @class(['is-off' => ! $day['working'] && ! $record, 'is-alert' => $day['missing']])>
                            <td class="center">{{ $day['date']->day }}</td>
                            <td class="nowrap">{{ thai_day($day['date']->dayOfWeek, true) }}</td>
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

        <div class="signs">
            <x-doc.signature role="พนักงาน" :name="$employee->full_name" />
            <x-doc.signature role="หัวหน้างาน" :name="$employee->manager?->full_name" />
            <x-doc.signature role="ฝ่ายบุคคล" />
        </div>

        <x-doc.footer />
    </article>
@endsection
