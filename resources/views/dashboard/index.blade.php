@extends('layouts.app')
@section('title', 'แดชบอร์ด')

@section('content')
    <x-page-header
        title="สวัสดี, {{ auth()->user()->name }} 👋"
        subtitle="ภาพรวมระบบบริหารทรัพยากรบุคคล ณ วันที่ {{ now()->translatedFormat('j F Y') }}" />

    <div class="stats">
        <x-stat label="พนักงานทั้งหมด" :value="number_format($metrics['total_employees'])" icon="users" color="blue"
                :meta="'ปฏิบัติงาน '.number_format($metrics['active_employees']).' คน'" />
        <x-stat label="กำลังลาวันนี้" :value="number_format($metrics['on_leave_today'])" icon="calendar" color="amber" meta="พนักงานที่ได้รับอนุมัติ" />
        <x-stat label="รออนุมัติการลา" :value="number_format($metrics['pending_requests'])" icon="check-circle" color="red" meta="คำขอที่ต้องดำเนินการ" />
        <x-stat label="ลงเวลาวันนี้" :value="number_format($metrics['present_today'])" icon="clock" color="green" meta="แผนกทั้งหมด {{ $metrics['departments'] }} แผนก" />
    </div>

    <div class="grid grid--2">
        <div class="grid" style="align-content:start;">
            <x-card title="จำนวนพนักงานแยกตามแผนก">
                @forelse($headcount as $dept)
                    @php $max = max($headcount->max('employees_count'), 1); @endphp
                    <div style="margin-bottom:14px;">
                        <div class="flex justify-between" style="margin-bottom:5px;">
                            <span style="font-weight:500;">{{ $dept->name }}</span>
                            <span class="cell-sub">{{ $dept->employees_count }} คน</span>
                        </div>
                        <div class="progress"><span style="width: {{ round($dept->employees_count / $max * 100) }}%"></span></div>
                    </div>
                @empty
                    <x-empty icon="building" title="ยังไม่มีข้อมูลแผนก" message="เพิ่มแผนกและพนักงานเพื่อดูสถิติ" />
                @endforelse
            </x-card>

            <x-card title="คำขอลาล่าสุด">
                <x-slot:actions>
                    <a href="{{ route('leave-requests.index') }}" class="btn btn--ghost btn--sm">ดูทั้งหมด</a>
                </x-slot:actions>
                @if($recentLeaves->isEmpty())
                    <x-empty icon="calendar" title="ยังไม่มีคำขอลา" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr><th>พนักงาน</th><th>ประเภท</th><th>ช่วงวันที่</th><th>สถานะ</th></tr>
                            </thead>
                            <tbody>
                                @foreach($recentLeaves as $leave)
                                    <tr>
                                        <td class="cell-strong">{{ $leave->employee?->full_name ?? '—' }}</td>
                                        <td>{{ $leave->leaveType?->name }}</td>
                                        <td class="cell-sub">{{ $leave->start_date->format('d/m/Y') }} – {{ $leave->end_date->format('d/m/Y') }}</td>
                                        <td><x-status-badge type="leave" :value="$leave->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="grid" style="align-content:start;">
            @if($myEmployee)
                <x-card title="ข้อมูลของฉัน">
                    <div class="flex items-center gap-2" style="margin-bottom:14px;">
                        <span class="avatar avatar--lg" style="background: {{ avatar_color($myEmployee->email) }}">{{ $myEmployee->initials }}</span>
                        <div>
                            <div class="cell-strong" style="font-size:16px;">{{ $myEmployee->full_name }}</div>
                            <div class="cell-sub">{{ $myEmployee->position?->title ?? 'ยังไม่ระบุตำแหน่ง' }}</div>
                            <div style="margin-top:6px;"><x-status-badge type="employee" :value="$myEmployee->status" /></div>
                        </div>
                    </div>
                    <dl class="dl">
                        <dt>รหัสพนักงาน</dt><dd>{{ $myEmployee->employee_code }}</dd>
                        <dt>แผนก</dt><dd>{{ $myEmployee->department?->name ?? '—' }}</dd>
                        <dt>วันเริ่มงาน</dt><dd>{{ $myEmployee->hire_date?->translatedFormat('j M Y') ?? '—' }}</dd>
                    </dl>
                </x-card>
            @endif

            <x-card title="ประกาศล่าสุด">
                <x-slot:actions>
                    <a href="{{ route('announcements.index') }}" class="btn btn--ghost btn--sm">ดูทั้งหมด</a>
                </x-slot:actions>
                @if($announcements->isEmpty())
                    <x-empty icon="megaphone" title="ยังไม่มีประกาศ" />
                @else
                    <ul class="divide-list">
                        @foreach($announcements as $a)
                            <li>
                                <div class="flex items-center gap-2" style="margin-bottom:4px;">
                                    @if($a->pinned)<x-icon name="pin" width="14" height="14" />@endif
                                    <x-status-badge type="announcement" :value="$a->category" />
                                </div>
                                <a href="{{ route('announcements.show', $a) }}" class="cell-strong">{{ $a->title }}</a>
                                <div class="cell-sub">{{ optional($a->published_at)->translatedFormat('j M Y') }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="พนักงานเข้าใหม่">
                @if($newHires->isEmpty())
                    <x-empty icon="users" title="ยังไม่มีข้อมูล" />
                @else
                    <ul class="divide-list">
                        @foreach($newHires as $emp)
                            <li class="flex items-center gap-2">
                                <span class="avatar" style="background: {{ avatar_color($emp->email) }}">{{ $emp->initials }}</span>
                                <div style="flex:1;">
                                    <div class="cell-strong">{{ $emp->full_name }}</div>
                                    <div class="cell-sub">{{ $emp->position?->title ?? '—' }} · {{ $emp->department?->name ?? '—' }}</div>
                                </div>
                                <span class="cell-sub">{{ $emp->hire_date?->translatedFormat('j M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
