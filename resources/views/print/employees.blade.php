@extends('layouts.print')

@php
    $counts = $employees->countBy('status');
@endphp

@section('orientation', 'landscape')
@section('title', 'ทะเบียนรายชื่อพนักงาน')
@section('filename', 'ทะเบียนรายชื่อพนักงาน_'.thai_date(now(), 'numeric'))
@section('back', route('employees.index', request()->only(['q', 'department_id', 'status'])))

@section('content')
    <article class="sheet sheet--landscape">
        <x-doc.letterhead title="ทะเบียนรายชื่อพนักงาน" subtitle="Employee register"
                          :meta="['ข้อมูล ณ วันที่' => thai_date(now()), 'จำนวน' => number_format($employees->count()).' คน']" />

        @if($filterSummary)
            <p class="doc-filter">เงื่อนไข: {{ implode(' · ', $filterSummary) }}</p>
        @endif

        <div class="doc-stats">
            <div class="doc-stat"><span>พนักงานทั้งหมด</span><b>{{ number_format($employees->count()) }} <small>คน</small></b></div>
            @foreach($statuses as $key => $label)
                <div class="doc-stat"><span>{{ $label }}</span><b>{{ number_format($counts->get($key, 0)) }}</b></div>
            @endforeach
        </div>

        @if($employees->isEmpty())
            <div class="doc-empty">ไม่พบพนักงานตามเงื่อนไขที่เลือก</div>
        @else
            <div class="doc-table-wrap">
                <table class="doc-table doc-table--compact doc-table--dense">
                    <thead>
                        <tr>
                            <th class="center">ลำดับ</th>
                            <th>รหัส</th>
                            <th>ชื่อ–สกุล</th>
                            <th>แผนก</th>
                            <th>ตำแหน่ง</th>
                            <th>ประเภทการจ้าง</th>
                            <th>วันเริ่มงาน</th>
                            <th>อายุงาน</th>
                            <th>โทรศัพท์</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $i => $employee)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td class="nowrap">{{ $employee->employee_code }}</td>
                                <td>{{ $employee->full_name }}</td>
                                <td>{{ $employee->department?->name ?? '—' }}</td>
                                <td>{{ $employee->position?->title ?? '—' }}</td>
                                <td>{{ $types[$employee->employment_type] ?? '—' }}</td>
                                <td class="nowrap">{{ thai_date($employee->hire_date, 'short') }}</td>
                                <td class="nowrap">{{ $employee->hire_date ? thai_duration($employee->hire_date, $employee->termination_date ?? now()) : '—' }}</td>
                                <td class="nowrap">{{ $employee->phone ?? '—' }}</td>
                                <td class="nowrap">{{ $statuses[$employee->status] ?? $employee->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="signs" style="--cols: 2">
            <x-doc.signature role="ผู้จัดทำ" />
            <x-doc.signature role="ผู้ตรวจสอบ" />
        </div>

        <x-doc.footer note="ข้อมูลส่วนบุคคล — ใช้เพื่อการบริหารงานบุคคลภายในบริษัทเท่านั้น" />
    </article>
@endsection
