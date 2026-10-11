@extends('layouts.print')

@php
    $days = fn ($value) => rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
    $genders = ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'อื่น ๆ'];
    $leaveLabels = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่อนุมัติ', 'cancelled' => 'ยกเลิก'];
    $serviceEnd = $employee->termination_date ?? now();
@endphp

@section('title', 'ทะเบียนประวัติ · '.$employee->full_name)
@section('filename', 'ทะเบียนประวัติ_'.$employee->employee_code)
@section('back', route('employees.show', $employee))

@section('content')
    <article class="sheet">
        <x-doc.letterhead title="ทะเบียนประวัติพนักงาน" subtitle="Employee record"
                          :meta="['รหัสพนักงาน' => $employee->employee_code, 'ข้อมูล ณ วันที่' => thai_date(now())]" />

        <div class="doc-headline">
            <div class="doc-headline__sub">ข้อมูลส่วนบุคคลตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล — ใช้เพื่อการบริหารงานบุคคลเท่านั้น</div>
            <span class="doc-flag">ลับ · CONFIDENTIAL</span>
        </div>

        <div class="profile">
            <div class="profile__photo">
                @if($employee->photo_url)
                    <img src="{{ doc_src($employee->photo_url, \Illuminate\Support\Facades\Storage::disk('public')->path($employee->photo_path)) }}" alt="">
                @else
                    {{ $employee->initials }}
                @endif
            </div>
            <div>
                <div class="profile__name">{{ $employee->full_name }}</div>
                <div class="profile__sub">{{ $employee->position?->title ?? 'ยังไม่ระบุตำแหน่ง' }} · {{ $employee->department?->name ?? 'ยังไม่ระบุแผนก' }}</div>
                <div class="tags">
                    <span class="tag">{{ $statusLabel }}</span>
                    <span class="tag">{{ $typeLabel }}</span>
                </div>
                <dl class="kv">
                    <div class="kv__row"><dt>วันเริ่มงาน</dt><dd>{{ thai_date($employee->hire_date) }}</dd></div>
                    <div class="kv__row"><dt>อายุงาน</dt><dd>{{ $employee->hire_date ? thai_duration($employee->hire_date, $serviceEnd) : '—' }}</dd></div>
                    <div class="kv__row"><dt>หัวหน้างาน</dt><dd>{{ $employee->manager?->full_name ?? '—' }}</dd></div>
                    <div class="kv__row"><dt>อีเมล</dt><dd>{{ $employee->email }}</dd></div>
                </dl>
            </div>
        </div>

        <section class="doc-section">
            <h2 class="doc-section__title">ข้อมูลส่วนตัว</h2>
            <dl class="kv">
                <div class="kv__row"><dt>เลขประจำตัวประชาชน</dt><dd>{{ format_national_id($employee->national_id) }}</dd></div>
                <div class="kv__row"><dt>เพศ</dt><dd>{{ $genders[$employee->gender] ?? '—' }}</dd></div>
                <div class="kv__row"><dt>วันเกิด</dt><dd>{{ thai_date($employee->date_of_birth) }}</dd></div>
                <div class="kv__row"><dt>อายุ</dt><dd>{{ $employee->date_of_birth ? $employee->date_of_birth->age.' ปี' : '—' }}</dd></div>
                <div class="kv__row"><dt>โทรศัพท์</dt><dd>{{ $employee->phone ?? '—' }}</dd></div>
                <div class="kv__row"><dt>อีเมล</dt><dd>{{ $employee->email }}</dd></div>
                <div class="kv__row kv__row--full"><dt>ที่อยู่</dt><dd>{{ $employee->address ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ข้อมูลการจ้างงาน</h2>
            <dl class="kv">
                <div class="kv__row"><dt>แผนก</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>ตำแหน่ง</dt><dd>{{ $employee->position?->title ?? '—' }}</dd></div>
                <div class="kv__row"><dt>ประเภทการจ้าง</dt><dd>{{ $typeLabel }}</dd></div>
                <div class="kv__row"><dt>สถานะ</dt><dd>{{ $statusLabel }}</dd></div>
                <div class="kv__row"><dt>วันเริ่มงาน</dt><dd>{{ thai_date($employee->hire_date) }}</dd></div>
                <div class="kv__row"><dt>วันสิ้นสุดการจ้าง</dt><dd>{{ thai_date($employee->termination_date) }}</dd></div>
                <div class="kv__row"><dt>เงินเดือน</dt><dd>{{ $employee->base_salary !== null ? number_format((float) $employee->base_salary, 2).' บาท' : '—' }}</dd></div>
                <div class="kv__row"><dt>หัวหน้างาน</dt><dd>{{ $employee->manager?->full_name ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ผู้ติดต่อกรณีฉุกเฉิน</h2>
            <dl class="kv">
                <div class="kv__row"><dt>ชื่อ–สกุล</dt><dd>{{ $employee->emergency_contact_name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>โทรศัพท์</dt><dd>{{ $employee->emergency_contact_phone ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">สิทธิ์วันลาปี {{ $year + 543 }} <small>หน่วย: วัน</small></h2>
            @if($balances->isEmpty())
                <div class="doc-empty">ยังไม่มีข้อมูลวันลาในปีนี้</div>
            @else
                <table class="doc-table doc-table--compact">
                    <thead>
                        <tr><th>ประเภทการลา</th><th class="num">สิทธิ์</th><th class="num">ใช้ไป</th><th class="num">รออนุมัติ</th><th class="num">คงเหลือ</th></tr>
                    </thead>
                    <tbody>
                        @foreach($balances as $balance)
                            <tr>
                                <td>{{ $balance->leaveType?->name ?? '—' }}</td>
                                <td class="num">{{ $days($balance->entitled_days) }}</td>
                                <td class="num">{{ $days($balance->used_days) }}</td>
                                <td class="num">{{ $days($balance->pending_days) }}</td>
                                <td class="num"><b>{{ $days($balance->remaining_days) }}</b></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ประวัติการลาล่าสุด <small>{{ $leaveRequests->count() }} รายการ</small></h2>
            @if($leaveRequests->isEmpty())
                <div class="doc-empty">ยังไม่มีคำขอลา</div>
            @else
                <table class="doc-table doc-table--compact">
                    <thead>
                        <tr><th>ประเภท</th><th>ตั้งแต่</th><th>ถึง</th><th class="num">วัน</th><th>สถานะ</th></tr>
                    </thead>
                    <tbody>
                        @foreach($leaveRequests as $request)
                            <tr>
                                <td>{{ $request->leaveType?->name ?? '—' }}</td>
                                <td class="nowrap">{{ thai_date($request->start_date, 'short') }}</td>
                                <td class="nowrap">{{ thai_date($request->end_date, 'short') }}</td>
                                <td class="num">{{ $days($request->total_days) }}</td>
                                <td>{{ $leaveLabels[$request->status] ?? $request->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">การฝึกอบรม</h2>
            @if($trainings->isEmpty())
                <div class="doc-empty">ยังไม่มีประวัติการฝึกอบรม</div>
            @else
                <table class="doc-table doc-table--compact">
                    <thead>
                        <tr><th>หลักสูตร</th><th class="num">ชั่วโมง</th><th>วันที่อบรม</th><th>ผล</th></tr>
                    </thead>
                    <tbody>
                        @foreach($trainings as $enrollment)
                            <tr>
                                <td>{{ $enrollment->course?->title ?? '—' }}</td>
                                <td class="num">{{ $enrollment->course?->hours ?? '—' }}</td>
                                <td class="nowrap">{{ thai_date($enrollment->course?->scheduled_date, 'short') }}</td>
                                <td>{{ match ($enrollment->status) {
                                    'completed' => 'จบหลักสูตร '.thai_date($enrollment->completed_at, 'short'),
                                    'cancelled' => 'ยกเลิก',
                                    default => 'ลงทะเบียนแล้ว',
                                } }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ผลการประเมินการปฏิบัติงาน</h2>
            @if($reviews->isEmpty())
                <div class="doc-empty">ยังไม่มีผลการประเมิน</div>
            @else
                <table class="doc-table doc-table--compact">
                    <thead>
                        <tr><th>งวดการประเมิน</th><th class="num">คะแนน</th><th>วันที่ประเมิน</th></tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                            <tr>
                                <td>{{ $review->period }}</td>
                                <td class="num">{{ $review->score ? $review->score.' / 5' : 'ยังไม่ให้คะแนน' }}</td>
                                <td class="nowrap">{{ thai_date($review->reviewed_at, 'short') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <div class="signs" style="--cols: 2">
            <x-doc.signature role="พนักงาน (รับรองว่าข้อมูลถูกต้อง)" :name="$employee->full_name" />
            <x-doc.signature role="เจ้าหน้าที่ฝ่ายบุคคล" />
        </div>

        <x-doc.footer note="เอกสารลับ — ห้ามเผยแพร่หรือส่งต่อโดยไม่ได้รับอนุญาต" />
    </article>
@endsection
