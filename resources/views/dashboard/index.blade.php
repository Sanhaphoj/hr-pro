@extends('layouts.app')
@section('title', 'แดชบอร์ด')

@php
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $today = $personal['today'] ?? null;
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'อรุณสวัสดิ์' : ($hour < 17 ? 'สวัสดีตอนบ่าย' : 'สวัสดีตอนเย็น');
    // Organisation-wide panels lead the main column for the roles that read
    // them; everyone else gets their own leave and the company news there.
    $orgView = $can['org'] || $can['reports'];
@endphp

@section('content')
    {{-- ---------------- Hero: greeting, today's punch, the things people came to do ---------------- --}}
    <section class="hero">
        <div class="hero__row">
            <div class="hero__text">
                <div class="hero__eyebrow">วัน{{ thai_day(now()->dayOfWeek) }}ที่ {{ thai_date(now()) }}</div>
                <h1>{{ $greeting }}, {{ $myEmployee?->first_name ?? auth()->user()->name }} 👋</h1>
                <p class="hero__sub">
                    @if($today?->clock_out)
                        วันนี้คุณลงเวลาครบแล้ว เข้า {{ $today->clock_in->format('H:i') }} – ออก {{ $today->clock_out->format('H:i') }} น. ขอบคุณที่ทำงานหนักนะครับ
                    @elseif($today?->clock_in)
                        คุณเข้างานเมื่อ {{ $today->clock_in->format('H:i') }} น.{{ $today->status === 'late' ? ' (มาสาย)' : '' }} อย่าลืมลงเวลาออกก่อนกลับบ้าน
                    @elseif($myEmployee && ! in_array(now()->dayOfWeekIso, (array) config('hrpro.work.working_days', [1, 2, 3, 4, 5]), true))
                        วันนี้เป็นวันหยุด พักผ่อนให้เต็มที่นะครับ
                    @elseif($myEmployee)
                        วันนี้คุณยังไม่ได้ลงเวลาเข้างาน
                    @else
                        ภาพรวมระบบบริหารทรัพยากรบุคคลของ {{ config('hrpro.company_name') }}
                    @endif
                </p>
            </div>

            <div class="hero__actions">
                @if($myEmployee && ! $today?->clock_out)
                    <a href="{{ route('time-clock.show') }}" class="hero__chip hero__chip--solid">
                        <x-icon name="clock" />{{ $today?->clock_in ? 'ลงเวลาออกงาน' : 'ลงเวลาเข้างาน' }}
                    </a>
                @endif
                <a href="{{ route('leave-requests.create') }}" class="hero__chip">
                    <x-icon name="calendar" />ยื่นใบลา
                </a>
                <a href="{{ route('leave-calendar.index') }}" class="hero__chip">
                    <x-icon name="list" />ปฏิทินวันลา
                </a>
            </div>
        </div>
    </section>

    {{-- ---------------- Headline figures ---------------- --}}
    @if($metrics)
        <div class="kpi-grid">
            <x-kpi label="พนักงานทั้งหมด" :value="number_format($metrics['total_employees'])" icon="users" tone="teal"
                   :meta="'ปฏิบัติงาน '.number_format($metrics['active_employees']).' คน · '.$metrics['departments'].' แผนก'"
                   :href="route('employees.index')" />
            <x-kpi label="ลงเวลาวันนี้" :value="number_format($metrics['present_today'])" icon="clock" tone="green"
                   :meta="$metrics['active_employees'] ? 'คิดเป็น '.round($metrics['present_today'] / $metrics['active_employees'] * 100).'% ของพนักงาน' : 'บันทึกการเข้างานของวันนี้'"
                   :href="auth()->user()->hasPermission('attendance.viewAll') ? route('attendance.index', ['date' => now()->toDateString()]) : null" />
            <x-kpi label="กำลังลาวันนี้" :value="number_format($metrics['on_leave_today'])" icon="calendar" tone="amber"
                   meta="ดูได้ในปฏิทินวันลา" :href="route('leave-calendar.index')" />
            @if($can['approve'])
                <x-kpi label="รออนุมัติการลา" :value="number_format($metrics['pending_requests'])" icon="check-circle" tone="red"
                       meta="คำขอที่ต้องดำเนินการ" :href="route('leave-approvals.index')" />
            @endif
        </div>
    @elseif($personal)
        @php $remaining = $personal['balances']->sum(fn ($b) => $b->remaining_days); @endphp
        <div class="kpi-grid">
            <x-kpi label="วันลาคงเหลือ" :value="$fmt($remaining)" icon="calendar" tone="teal"
                   :meta="'รวมทุกประเภท ปี '.(now()->year + 543)" :href="route('leave-requests.index')" />
            <x-kpi label="ชั่วโมงทำงานเดือนนี้" :value="$fmt($personal['worked_hours'])" icon="clock" tone="green"
                   :meta="thai_month(now()->month).' '.(now()->year + 543)" :href="route('attendance.index')" />
            <x-kpi label="มาสายเดือนนี้" :value="number_format($personal['late_days'])" icon="alert" :tone="$personal['late_days'] ? 'amber' : 'green'"
                   :meta="$personal['late_days'] ? 'ครั้ง' : 'ยอดเยี่ยม ไม่เคยสายเลย'" />
            <x-kpi label="ใบลารออนุมัติ" :value="number_format($personal['pending'])" icon="check-circle" tone="blue"
                   meta="คำขอของฉันที่ยังรอผล" :href="route('leave-requests.index')" />
        </div>
    @endif

    <div class="grid grid--2">
        {{-- ---------------- Main column ---------------- --}}
        <div class="grid content-start">
            @if($can['reports'])
                @php
                    $rows = array_map(fn ($d) => [
                        'label' => $d['date']->day,
                        'tip' => thai_day($d['date']->dayOfWeek).' '.thai_date($d['date'], 'short'),
                        'values' => ['on_time' => $d['on_time'], 'late' => $d['late']],
                    ], $trend);
                    $total = array_sum(array_map(fn ($d) => $d['on_time'] + $d['late'], $trend));
                    $late = array_sum(array_column($trend, 'late'));
                @endphp
                <x-card title="การลงเวลาเข้างาน" :subtitle="count($trend).' วันทำการล่าสุด · มาสาย '.($total ? round($late / $total * 100, 1) : 0).'% ของการลงเวลาทั้งหมด'">
                    @if($total === 0)
                        <x-empty icon="clock" title="ยังไม่มีการลงเวลา" message="เมื่อพนักงานเริ่มลงเวลา กราฟแนวโน้มจะปรากฏที่นี่" />
                    @else
                        <x-chart.columns :rows="$rows" :series="['on_time' => 'ตรงเวลา', 'late' => 'มาสาย']" unit="คน"
                                         caption="จำนวนพนักงานที่ลงเวลาตรงเวลาและมาสายในแต่ละวัน" />
                    @endif
                </x-card>
            @endif

            @if($can['org'])
                <x-card title="จำนวนพนักงานแยกตามแผนก" subtitle="สัดส่วนกำลังคนในแต่ละหน่วยงาน">
                    @if($headcount->isEmpty())
                        <x-empty icon="building" title="ยังไม่มีข้อมูลแผนก" message="เพิ่มแผนกและพนักงานเพื่อดูสถิติกำลังคน" />
                    @else
                        <x-chart.bars :rows="$headcount->map(fn ($d) => ['label' => $d->name, 'value' => $d->employees_count])->all()" unit="คน" />
                    @endif
                </x-card>
            @endif

            @if($can['reports'] && $leaveByType->isNotEmpty())
                <x-card title="วันลาที่อนุมัติแล้วปีนี้" subtitle="รวมจำนวนวันแยกตามประเภทการลา">
                    <x-chart.bars :rows="$leaveByType->map(fn ($t) => ['label' => $t['name'], 'value' => $t['days']])->all()" unit="วัน" />
                </x-card>
            @endif

            @if(! $orgView && $personal)
                @include('dashboard._balances')
            @endif

            @if($can['approve'])
                <x-card title="คำขอลาล่าสุด" subtitle="รายการที่ยื่นเข้ามาใหม่ที่สุด" :padding="false">
                    <x-slot:actions>
                        <a href="{{ route('leave-approvals.index') }}" class="btn btn--ghost btn--sm">ไปที่การอนุมัติ</a>
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
                                            <td class="cell-sub" data-label="ช่วงวันที่">{{ thai_date($leave->start_date, 'short') }} – {{ thai_date($leave->end_date, 'short') }}</td>
                                            <td data-label="สถานะ"><x-status-badge type="leave" :value="$leave->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            @endif

            @if(! $orgView)
                @include('dashboard._announcements')
            @endif
        </div>

        {{-- ---------------- Side column ---------------- --}}
        <div class="grid content-start">
            @if($orgView && $personal)
                @include('dashboard._balances')
            @endif

            @if($team->isNotEmpty())
                @php
                    $states = [
                        'late' => ['amber', 'มาสาย'],
                        'absent' => ['gray', 'ยังไม่ลงเวลา'],
                        'leave' => ['blue', 'ลางาน'],
                        'in' => ['green', 'เข้างาน'],
                        'out' => ['gray', 'ออกงานแล้ว'],
                    ];
                    $present = $team->whereIn('state', ['in', 'late', 'out'])->count();
                @endphp
                <x-card title="ทีมของฉันวันนี้" :subtitle="'มาทำงาน '.$present.' จาก '.$team->count().' คน'" :padding="false">
                    <ul class="divide-list divide-list--inset">
                        @foreach($team->take(8) as $row)
                            <li class="person">
                                <span class="avatar" style="background: {{ avatar_color($row['employee']->email) }}">{{ $row['employee']->initials }}</span>
                                <div class="person__body">
                                    <div class="person__name">{{ $row['employee']->full_name }}</div>
                                    <div class="person__meta">{{ $row['employee']->position?->title ?? '—' }}</div>
                                </div>
                                <x-badge :color="$states[$row['state']][0]" dot>
                                    {{ $states[$row['state']][1] }}@if($row['time'] && in_array($row['state'], ['in', 'late'])) {{ $row['time']->format('H:i') }}@endif
                                </x-badge>
                            </li>
                        @endforeach
                    </ul>
                    @if($team->count() > 8)
                        <div class="card__footer cell-sub">และอีก {{ $team->count() - 8 }} คน</div>
                    @endif
                </x-card>
            @endif

            @if($celebrations->isNotEmpty())
                <x-card :title="'โอกาสพิเศษเดือน'.thai_month(now()->month)" :padding="false">
                    <ul class="divide-list divide-list--inset">
                        @foreach($celebrations as $event)
                            <li @class(['person', 'celebrate--today' => $event['today']])>
                                <span class="celebrate__icon" aria-hidden="true">{{ $event['kind'] === 'birthday' ? '🎂' : '🎉' }}</span>
                                <div class="person__body">
                                    <div class="person__name">{{ $event['employee']->full_name }}</div>
                                    <div class="person__meta">
                                        {{ $event['kind'] === 'birthday' ? 'วันเกิด' : 'ครบรอบทำงาน '.$event['years'].' ปี' }}
                                    </div>
                                </div>
                                <span class="person__aside">{{ $event['today'] ? 'วันนี้!' : $event['day'].' '.thai_month(now()->month, true) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if($orgView)
                @include('dashboard._announcements')
            @endif

            @if($can['org'])
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
                                    <span class="person__aside">{{ thai_date($emp->hire_date, 'short') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
@endsection
