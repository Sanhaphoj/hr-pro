@extends('layouts.app')
@section('title', 'แดชบอร์ด')

@section('content')
    {{-- ---------------- Hero: greeting + the three things people came to do ---------------- --}}
    <section class="hero">
        <div class="hero__row">
            <div class="hero__text">
                <div class="hero__eyebrow">{{ now()->translatedFormat('l j F') }} {{ now()->year + 543 }}</div>
                <h1>สวัสดี, {{ auth()->user()->name }} 👋</h1>
                <p class="hero__sub">ภาพรวมระบบบริหารทรัพยากรบุคคลของ {{ config('hrpro.company_name') }}</p>
            </div>

            <div class="hero__actions">
                <a href="{{ route('time-clock.show') }}" class="hero__chip hero__chip--solid">
                    <x-icon name="clock" />ลงเวลาเข้า–ออก
                </a>
                <a href="{{ route('leave-requests.create') }}" class="hero__chip">
                    <x-icon name="calendar" />ยื่นใบลา
                </a>
                <a href="{{ route('attendance.index') }}" class="hero__chip">
                    <x-icon name="history" />ประวัติการลงเวลา
                </a>
            </div>
        </div>
    </section>

    {{-- ---------------- Headline figures ---------------- --}}
    <div class="kpi-grid">
        <x-kpi label="พนักงานทั้งหมด" :value="number_format($metrics['total_employees'])" icon="users" tone="teal"
               :meta="'ปฏิบัติงาน '.number_format($metrics['active_employees']).' คน · '.$metrics['departments'].' แผนก'"
               :href="route('employees.index')" />
        <x-kpi label="ลงเวลาวันนี้" :value="number_format($metrics['present_today'])" icon="clock" tone="green"
               meta="บันทึกการเข้างานของวันนี้"
               :href="route('attendance.index', ['date' => now()->toDateString()])" />
        <x-kpi label="กำลังลาวันนี้" :value="number_format($metrics['on_leave_today'])" icon="calendar" tone="amber"
               meta="พนักงานที่ได้รับอนุมัติแล้ว" />
        <x-kpi label="รออนุมัติการลา" :value="number_format($metrics['pending_requests'])" icon="check-circle" tone="red"
               meta="คำขอที่ต้องดำเนินการ"
               :href="route('leave-approvals.index')" />
    </div>

    <div class="grid grid--2">
        {{-- ---------------- Main column ---------------- --}}
        <div class="grid content-start">
            <x-card title="จำนวนพนักงานแยกตามแผนก" subtitle="สัดส่วนกำลังคนในแต่ละหน่วยงาน">
                @if($headcount->isEmpty())
                    <x-empty icon="building" title="ยังไม่มีข้อมูลแผนก" message="เพิ่มแผนกและพนักงานเพื่อดูสถิติกำลังคน" />
                @else
                    @php $max = max($headcount->max('employees_count'), 1); @endphp
                    <ul class="divide-list">
                        @foreach($headcount as $dept)
                            <li>
                                <div class="flex justify-between items-center gap-2" style="margin-bottom:7px;">
                                    <span class="cell-strong">{{ $dept->name }}</span>
                                    <span class="cell-sub num">{{ $dept->employees_count }} คน</span>
                                </div>
                                <div class="progress"><span style="width: {{ round($dept->employees_count / $max * 100) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="คำขอลาล่าสุด" subtitle="รายการที่ยื่นเข้ามาใหม่ที่สุด" :padding="false">
                <x-slot:actions>
                    <a href="{{ route('leave-requests.index') }}" class="btn btn--ghost btn--sm">ดูทั้งหมด</a>
                </x-slot:actions>
                @if($recentLeaves->isEmpty())
                    <x-empty icon="calendar" title="ยังไม่มีคำขอลา" message="เมื่อมีพนักงานยื่นใบลา รายการจะปรากฏที่นี่" />
                @else
                    <div class="table-wrap">
                        <table class="table table--cards">
                            <thead>
                                <tr><th>พนักงาน</th><th>ประเภท</th><th>ช่วงวันที่</th><th>สถานะ</th></tr>
                            </thead>
                            <tbody>
                                @foreach($recentLeaves as $leave)
                                    <tr>
                                        <td class="cell-strong">{{ $leave->employee?->full_name ?? '—' }}</td>
                                        <td data-label="ประเภท">{{ $leave->leaveType?->name }}</td>
                                        <td class="cell-sub" data-label="ช่วงวันที่">{{ $leave->start_date->format('d/m/Y') }} – {{ $leave->end_date->format('d/m/Y') }}</td>
                                        <td data-label="สถานะ"><x-status-badge type="leave" :value="$leave->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- ---------------- Side column ---------------- --}}
        <div class="grid content-start">
            @if($myEmployee)
                <x-card title="ข้อมูลของฉัน">
                    <x-slot:actions>
                        <a href="{{ route('profile.edit') }}" class="btn btn--ghost btn--sm">แก้ไข</a>
                    </x-slot:actions>

                    <div class="person" style="margin-bottom:16px;">
                        <span class="avatar avatar--lg" style="background: {{ avatar_color($myEmployee->email) }}">{{ $myEmployee->initials }}</span>
                        <div class="person__body">
                            <div class="cell-strong" style="font-size:16px;">{{ $myEmployee->full_name }}</div>
                            <div class="person__meta">{{ $myEmployee->position?->title ?? 'ยังไม่ระบุตำแหน่ง' }}</div>
                            <div class="mt-1"><x-status-badge type="employee" :value="$myEmployee->status" /></div>
                        </div>
                    </div>

                    <dl class="dl">
                        <dt>รหัสพนักงาน</dt><dd class="num">{{ $myEmployee->employee_code }}</dd>
                        <dt>แผนก</dt><dd>{{ $myEmployee->department?->name ?? '—' }}</dd>
                        <dt>วันเริ่มงาน</dt><dd>{{ $myEmployee->hire_date?->translatedFormat('j M Y') ?? '—' }}</dd>
                    </dl>
                </x-card>
            @endif

            <x-card title="ประกาศล่าสุด" :padding="false">
                <x-slot:actions>
                    <a href="{{ route('announcements.index') }}" class="btn btn--ghost btn--sm">ดูทั้งหมด</a>
                </x-slot:actions>
                @if($announcements->isEmpty())
                    <x-empty icon="megaphone" title="ยังไม่มีประกาศ" message="ประกาศจากฝ่ายบุคคลจะแสดงที่นี่" />
                @else
                    <ul class="divide-list divide-list--inset">
                        @foreach($announcements as $a)
                            <li>
                                <div class="flex items-center gap-1" style="margin-bottom:5px;">
                                    @if($a->pinned)<x-icon name="pin" width="14" height="14" />@endif
                                    <x-status-badge type="announcement" :value="$a->category" />
                                </div>
                                <a href="{{ route('announcements.show', $a) }}" class="cell-strong">{{ $a->title }}</a>
                                <div class="person__meta">{{ optional($a->published_at)->translatedFormat('j M Y') }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="พนักงานเข้าใหม่" :padding="false">
                @if($newHires->isEmpty())
                    <x-empty icon="users" title="ยังไม่มีพนักงานเข้าใหม่" message="พนักงานที่เพิ่งเริ่มงานจะแสดงที่นี่" />
                @else
                    <ul class="divide-list divide-list--inset">
                        @foreach($newHires as $emp)
                            <li class="person">
                                <span class="avatar" style="background: {{ avatar_color($emp->email) }}">{{ $emp->initials }}</span>
                                <div class="person__body">
                                    <div class="person__name">{{ $emp->full_name }}</div>
                                    <div class="person__meta">{{ $emp->position?->title ?? '—' }} · {{ $emp->department?->name ?? '—' }}</div>
                                </div>
                                <span class="person__aside">{{ $emp->hire_date?->translatedFormat('j M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
