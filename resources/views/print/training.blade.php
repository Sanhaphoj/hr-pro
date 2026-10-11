@extends('layouts.print')

@php
    // A few empty lines at the foot of the sheet for walk-in attendees.
    $spareRows = 3;
@endphp

@section('title', 'ใบลงชื่อเข้าอบรม · '.$course->title)
@section('filename', 'ใบลงชื่อเข้าอบรม_'.\Illuminate\Support\Str::limit($course->title, 60, ''))
@section('back', route('training.show', $course))

@section('content')
    <article class="sheet">
        <x-doc.letterhead title="ใบลงชื่อผู้เข้ารับการอบรม" subtitle="Training attendance sheet"
                          :meta="['เลขที่' => doc_no('TR', $course->id, $course->scheduled_date ?? $course->created_at), 'วันที่อบรม' => thai_date($course->scheduled_date)]" />

        <dl class="kv">
            <div class="kv__row kv__row--full"><dt>หลักสูตร</dt><dd>{{ $course->title }}</dd></div>
            <div class="kv__row"><dt>วันที่อบรม</dt><dd>{{ $course->scheduled_date ? 'วัน'.thai_day($course->scheduled_date->dayOfWeek).'ที่ '.thai_date($course->scheduled_date) : '..............................' }}</dd></div>
            <div class="kv__row"><dt>จำนวนชั่วโมง</dt><dd>{{ $course->hours }} ชั่วโมง</dd></div>
            <div class="kv__row"><dt>สถานที่</dt><dd>..............................................</dd></div>
            <div class="kv__row"><dt>วิทยากร</dt><dd>..............................................</dd></div>
            <div class="kv__row"><dt>ผู้ลงทะเบียน</dt><dd>{{ $course->enrollments->count() }} คน</dd></div>
            <div class="kv__row"><dt>เวลา</dt><dd>..................... ถึง ..................... น.</dd></div>
        </dl>

        @if($course->description)
            <section class="doc-section">
                <h2 class="doc-section__title">รายละเอียดหลักสูตร</h2>
                <div class="doc-text" style="min-height: 0;">{{ \Illuminate\Support\Str::limit($course->description, 600) }}</div>
            </section>
        @endif

        <section class="doc-section">
            <h2 class="doc-section__title">รายชื่อผู้เข้ารับการอบรม</h2>
            <table class="doc-table doc-table--tall">
                <thead>
                    <tr>
                        <th class="center" style="width: 12mm;">ลำดับ</th>
                        <th style="width: 24mm;">รหัส</th>
                        <th>ชื่อ–สกุล</th>
                        <th>แผนก</th>
                        <th style="width: 44mm;">ลายมือชื่อ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($course->enrollments as $i => $enrollment)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="nowrap">{{ $enrollment->employee?->employee_code ?? '—' }}</td>
                            <td>
                                {{ $enrollment->employee?->full_name ?? '—' }}
                                @if($enrollment->status === 'completed')
                                    <span class="doc-sub">จบหลักสูตรแล้ว</span>
                                @endif
                            </td>
                            <td>{{ $enrollment->employee?->department?->name ?? '—' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    @for($n = 1; $n <= $spareRows; $n++)
                        <tr>
                            <td class="center">{{ $course->enrollments->count() + $n }}</td>
                            <td></td><td></td><td></td><td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </section>

        <div class="signs" style="--cols: 2">
            <x-doc.signature role="ผู้ประสานงานหลักสูตร" />
            <x-doc.signature role="วิทยากร" />
        </div>

        <x-doc.footer />
    </article>
@endsection
