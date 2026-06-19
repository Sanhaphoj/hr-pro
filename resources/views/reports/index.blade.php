@extends('layouts.app')
@section('title', 'รายงานและสถิติ')

@section('content')
    <x-page-header
        title="รายงานและสถิติ"
        subtitle="ภาพรวมเชิงวิเคราะห์ของบุคลากร การลา และการลงเวลา ประจำปี {{ $year + 543 }}" />

    {{-- Top-line headcount tiles --}}
    <div class="stats">
        <x-stat label="พนักงานทั้งหมด" :value="number_format($totalEmployees)" icon="users" color="blue" meta="ทุกสถานะการจ้าง" />
        <x-stat label="ปฏิบัติงานวันนี้" :value="number_format($attendanceToday['present'])" icon="check-circle" color="green"
                :meta="'มาสาย '.number_format($attendanceToday['late']).' คน'" />
        <x-stat label="วันลาที่ใช้ (ปีนี้)" :value="number_format($leaveUsageTotalDays, 1)" icon="calendar" color="amber" meta="เฉพาะที่อนุมัติแล้ว" />
        <x-stat label="ขาดงานวันนี้" :value="number_format($attendanceToday['absent'])" icon="x-circle" color="red"
                :meta="'ลางาน '.number_format($attendanceToday['on_leave']).' คน'" />
    </div>

    <div class="grid grid--2">
        {{-- Headcount by department --}}
        <x-card title="จำนวนพนักงานแยกตามแผนก">
            @php $deptMax = max($headcountByDepartment->max('employees_count') ?? 0, 1); @endphp
            @forelse($headcountByDepartment as $dept)
                <div style="margin-bottom:14px;">
                    <div class="flex justify-between" style="margin-bottom:5px;">
                        <span style="font-weight:500;">{{ $dept->name }}</span>
                        <span class="cell-sub">{{ number_format($dept->employees_count) }} คน</span>
                    </div>
                    <div class="progress"><span style="width: {{ round($dept->employees_count / $deptMax * 100) }}%"></span></div>
                </div>
            @empty
                <x-empty icon="building" title="ยังไม่มีข้อมูลแผนก" message="เพิ่มแผนกและพนักงานเพื่อดูสถิติ" />
            @endforelse
        </x-card>

        {{-- Headcount by status --}}
        <x-card title="จำนวนพนักงานแยกตามสถานะ">
            @php $statusMax = max($headcountByStatus->max('count') ?? 0, 1); @endphp
            @foreach($headcountByStatus as $row)
                <div style="margin-bottom:14px;">
                    <div class="flex justify-between" style="margin-bottom:5px;">
                        <span class="flex items-center gap-2">
                            <x-status-badge type="employee" :value="$row['key']" />
                        </span>
                        <span class="cell-sub">{{ number_format($row['count']) }} คน</span>
                    </div>
                    <div class="progress"><span style="width: {{ round($row['count'] / $statusMax * 100) }}%"></span></div>
                </div>
            @endforeach
        </x-card>
    </div>

    <div class="grid grid--2" style="margin-top:18px;">
        {{-- Headcount by employment type --}}
        <x-card title="จำนวนพนักงานแยกตามประเภทการจ้าง">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>ประเภทการจ้าง</th><th class="text-right">จำนวน</th><th class="text-right">สัดส่วน</th></tr>
                    </thead>
                    <tbody>
                        @foreach($headcountByEmploymentType as $row)
                            <tr>
                                <td><x-status-badge type="employment" :value="$row['key']" /></td>
                                <td class="cell-strong text-right">{{ number_format($row['count']) }}</td>
                                <td class="cell-sub text-right">
                                    {{ $totalEmployees > 0 ? round($row['count'] / $totalEmployees * 100) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Attendance summary today --}}
        <x-card title="สรุปการลงเวลาวันนี้" subtitle="ข้อมูล ณ {{ now()->translatedFormat('j F Y') }}">
            @if($attendanceToday['total'] === 0)
                <x-empty icon="clock" title="ยังไม่มีการลงเวลาวันนี้" message="ข้อมูลจะปรากฏเมื่อพนักงานเริ่มลงเวลา" />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>สถานะ</th><th class="text-right">จำนวน</th><th class="text-right">สัดส่วน</th></tr>
                        </thead>
                        <tbody>
                            @foreach(['present', 'late', 'half_day', 'on_leave', 'absent'] as $status)
                                <tr>
                                    <td><x-status-badge type="attendance" :value="$status" /></td>
                                    <td class="cell-strong text-right">{{ number_format($attendanceToday[$status]) }}</td>
                                    <td class="cell-sub text-right">
                                        {{ $attendanceToday['total'] > 0 ? round($attendanceToday[$status] / $attendanceToday['total'] * 100) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="cell-strong">รวมทั้งหมด</td>
                                <td class="cell-strong text-right">{{ number_format($attendanceToday['total']) }}</td>
                                <td class="cell-sub text-right">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Leave usage this year by leave type --}}
    <div style="margin-top:18px;">
        <x-card title="การใช้วันลาแยกตามประเภท (อนุมัติแล้ว)" subtitle="รวมจำนวนวันลาที่อนุมัติแล้วในปี {{ $year + 543 }}">
            @if($leaveUsage->isEmpty())
                <x-empty icon="calendar" title="ยังไม่มีประเภทการลา" message="กำหนดประเภทการลาเพื่อดูสถิติการใช้งาน" />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ประเภทการลา</th>
                                <th class="text-right">จำนวนคำขอ</th>
                                <th class="text-right">รวมวันลา</th>
                                <th>สัดส่วนการใช้งาน</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $leaveMax = max($leaveUsage->max('total_days') ?: 0, 1); @endphp
                            @foreach($leaveUsage as $row)
                                <tr>
                                    <td class="cell-strong">{{ $row['name'] }}</td>
                                    <td class="cell-sub text-right">{{ number_format($row['request_count']) }}</td>
                                    <td class="cell-strong text-right">{{ number_format($row['total_days'], 1) }} วัน</td>
                                    <td style="min-width:160px;">
                                        <div class="progress"><span style="width: {{ round($row['total_days'] / $leaveMax * 100) }}%"></span></div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="cell-strong">รวมทั้งหมด</td>
                                <td></td>
                                <td class="cell-strong text-right">{{ number_format($leaveUsageTotalDays, 1) }} วัน</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection
