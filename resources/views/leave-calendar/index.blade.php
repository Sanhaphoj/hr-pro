@extends('layouts.app')
@section('title', 'ปฏิทินวันลา')

@php
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $query = fn ($m) => array_filter(['month' => $m, 'department' => $viewAll ? $departmentId : null]);
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header__text">
            <h1>ปฏิทินวันลา</h1>
            <p>
                @if($viewAll)
                    วันลาของพนักงานทุกคน ทั้งที่อนุมัติแล้วและที่รออนุมัติ (เส้นประ)
                @else
                    วันลาของเพื่อนร่วมแผนก แสดงเพียงว่าลา ไม่แสดงประเภทการลา
                @endif
                · วันนี้มีคนลา {{ $awayToday }} คน
            </p>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('leave-requests.create') }}" class="btn btn--primary"><x-icon name="plus" />ยื่นใบลา</a>
        </div>
    </div>

    <x-card :padding="false">
        <div class="cal__bar">
            <div class="cal__nav">
                <a href="{{ route('leave-calendar.index', $query($prev)) }}" class="btn btn--secondary btn--sm" aria-label="เดือนก่อน"><x-icon name="chevron-left" width="16" height="16" /></a>
                <h2 class="cal__title">{{ thai_month($month->month) }} {{ $month->year + 543 }}</h2>
                <a href="{{ route('leave-calendar.index', $query($next)) }}" class="btn btn--secondary btn--sm" aria-label="เดือนถัดไป"><x-icon name="chevron-right" width="16" height="16" /></a>
                @unless($month->isSameMonth(now()))
                    <a href="{{ route('leave-calendar.index', $query(null)) }}" class="btn btn--ghost btn--sm">เดือนนี้</a>
                @endunless
            </div>
            @if($viewAll)
                <form method="GET" class="cal__filter">
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <select name="department" class="select select--sm" onchange="this.form.submit()" aria-label="กรองตามแผนก">
                        <option value="">ทุกแผนก</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected($departmentId === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <div class="cal" role="grid" aria-label="ปฏิทินวันลา {{ thai_month($month->month) }}">
            <div class="cal__head" role="row">
                @foreach([0, 1, 2, 3, 4, 5, 6] as $dow)
                    <div role="columnheader">{{ thai_day($dow, true) }}</div>
                @endforeach
            </div>
            @foreach($weeks as $week)
                <div class="cal__week" role="row">
                    @foreach($week as $day)
                        <div role="gridcell" @class([
                            'cal__day',
                            'is-out' => ! $day['inMonth'],
                            'is-today' => $day['isToday'],
                            'is-off' => ! $day['isWorkday'],
                        ])>
                            <div class="cal__date">
                                <span>{{ $day['date']->day }}</span>
                                @if($day['leaves']->isNotEmpty())
                                    <span class="cal__count">{{ $day['leaves']->count() }} คน</span>
                                @endif
                            </div>
                            <ul class="cal__list">
                                @foreach($day['leaves']->take(3) as $leave)
                                    @php $tag = $leave['url'] ? 'a' : 'span'; @endphp
                                    <li>
                                        <{{ $tag }} @if($leave['url']) href="{{ $leave['url'] }}" @endif @class([
                                            'cal__chip',
                                            'cal__chip--'.$leave['color'],
                                            'is-pending' => $leave['pending'],
                                            'is-mine' => $leave['mine'],
                                        ]) title="{{ $leave['name'] }}{{ $leave['type'] ? ' · '.$leave['type'] : '' }}{{ $leave['pending'] ? ' · รออนุมัติ' : '' }}">
                                            <span class="cal__dot" style="background: {{ avatar_color($leave['email']) }}">{{ $leave['initials'] }}</span>
                                            <span class="cal__name">{{ $leave['mine'] ? 'ฉัน' : $leave['name'] }}</span>
                                        </{{ $tag }}>
                                    </li>
                                @endforeach
                                @if($day['leaves']->count() > 3)
                                    <li class="cal__more" title="{{ $day['leaves']->skip(3)->pluck('name')->join(', ') }}">+{{ $day['leaves']->count() - 3 }} คน</li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </x-card>
@endsection
