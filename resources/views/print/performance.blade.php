@extends('layouts.print')

@php
    $employee = $review->employee;
    $scale = [
        5 => ['ดีเยี่ยม', 'ผลงานเกินความคาดหมายอย่างโดดเด่นและต่อเนื่อง'],
        4 => ['ดีมาก', 'ผลงานเกินเป้าหมายที่กำหนด'],
        3 => ['ดี', 'ผลงานเป็นไปตามเป้าหมายและมาตรฐานของตำแหน่ง'],
        2 => ['พอใช้', 'ผลงานต่ำกว่าเป้าหมายบางส่วน ควรได้รับการพัฒนา'],
        1 => ['ต้องปรับปรุง', 'ผลงานต่ำกว่ามาตรฐานอย่างชัดเจน ต้องมีแผนปรับปรุง'],
    ];
@endphp

@section('title', 'ผลการประเมิน · '.($employee?->full_name ?? '—').' · '.$review->period)
@section('filename', sprintf('ผลประเมิน_%s_%s', $employee?->employee_code ?? $review->id, $review->period))
@section('back', route('performance.show', $review))

@section('content')
    <article class="sheet">
        <x-doc.letterhead title="แบบบันทึกผลการประเมิน" subtitle="Performance review"
                          :meta="['เลขที่' => doc_no('PR', $review->id, $review->reviewed_at ?? $review->created_at), 'งวด' => $review->period]" />

        <section class="doc-section" style="margin-top: 0;">
            <h2 class="doc-section__title">ผู้ถูกประเมิน</h2>
            <dl class="kv">
                <div class="kv__row"><dt>ชื่อ–สกุล</dt><dd>{{ $employee?->full_name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>รหัสพนักงาน</dt><dd>{{ $employee?->employee_code ?? '—' }}</dd></div>
                <div class="kv__row"><dt>ตำแหน่ง</dt><dd>{{ $employee?->position?->title ?? '—' }}</dd></div>
                <div class="kv__row"><dt>แผนก</dt><dd>{{ $employee?->department?->name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>งวดการประเมิน</dt><dd>{{ $review->period }}</dd></div>
                <div class="kv__row"><dt>ผู้ประเมิน</dt><dd>{{ $review->reviewer?->name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>วันที่ประเมิน</dt><dd>{{ thai_date($review->reviewed_at) }}</dd></div>
            </dl>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ผลการประเมิน</h2>
            <div class="score">
                <div class="score__total">
                    <b>{{ $review->score }}<small> / 5</small></b>
                    <span>{{ $scale[$review->score][0] ?? '—' }}</span>
                </div>
                <table class="doc-table doc-table--compact">
                    <thead><tr><th class="center">ระดับ</th><th>ผลการประเมิน</th><th>ความหมาย</th><th class="center">ผล</th></tr></thead>
                    <tbody>
                        @foreach($scale as $level => [$name, $meaning])
                            <tr @class(['is-picked' => $level === $review->score])>
                                <td class="center">{{ $level }}</td>
                                <td class="nowrap">{{ $name }}</td>
                                <td>{{ $meaning }}</td>
                                <td class="center"><span @class(['check', 'is-on' => $level === $review->score])><i></i></span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">จุดแข็ง</h2>
            <div class="doc-text">{{ $review->strengths ?: '—' }}</div>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">สิ่งที่ควรพัฒนา</h2>
            <div class="doc-text">{{ $review->improvements ?: '—' }}</div>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ความเห็นของผู้ถูกประเมิน</h2>
            <div class="doc-lines" style="min-height: 16.5mm;"></div>
        </section>

        <div class="signs">
            <x-doc.signature role="ผู้ถูกประเมิน (รับทราบผล)" :name="$employee?->full_name" />
            <x-doc.signature role="ผู้ประเมิน" :name="$review->reviewer?->name" />
            <x-doc.signature role="ผู้บังคับบัญชาระดับถัดไป" />
        </div>

        <x-doc.footer note="เอกสารลับ — ผลการประเมินเป็นข้อมูลเฉพาะบุคคล" />
    </article>
@endsection
