@extends('layouts.print')

@php
    $employee = $payroll->employee;
    $periodDate = \Illuminate\Support\Carbon::create($payroll->period_year, $payroll->period_month, 1);
    $period = thai_month($payroll->period_month).' '.($payroll->period_year + 543);
    $earnings = (float) $payroll->base_salary + (float) $payroll->allowances;
    $draft = $payroll->status !== 'finalized';
    $money = fn ($value) => number_format((float) $value, 2);
    // Settings → Payroll. A month shorter than the pay day pays on its last day.
    $payDay = config('hrpro.payroll.pay_day');
    $payDate = $payDay ? $periodDate->copy()->day(min((int) $payDay, $periodDate->daysInMonth)) : null;
@endphp

@section('title', 'สลิปเงินเดือน · '.$period)
@section('filename', sprintf('สลิปเงินเดือน_%s_%d-%02d', $employee?->employee_code ?? $payroll->id, $payroll->period_year + 543, $payroll->period_month))
@section('back', route('payroll.show', $payroll))

@section('content')
    <article class="sheet">
        @if($draft)
            <div class="doc-watermark" aria-hidden="true"><span>ฉบับร่าง</span></div>
        @endif

        <x-doc.letterhead title="ใบแจ้งเงินเดือน" subtitle="Pay slip"
                          :meta="array_filter(['เลขที่' => doc_no('PAY', $payroll->id, $periodDate), 'งวด' => $period, 'วันที่จ่าย' => $payDate ? thai_date($payDate) : null])" />

        <div class="doc-headline">
            <div>
                <div class="doc-headline__title">สลิปเงินเดือนประจำงวด {{ $period }}</div>
                <div class="doc-headline__sub">เอกสารฉบับนี้เป็นความลับเฉพาะตัวพนักงาน</div>
            </div>
            <span class="doc-flag">ลับ · CONFIDENTIAL</span>
        </div>

        <section class="doc-section">
            <h2 class="doc-section__title">ข้อมูลพนักงาน</h2>
            <dl class="kv">
                <div class="kv__row"><dt>ชื่อ–สกุล</dt><dd>{{ $employee?->full_name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>รหัสพนักงาน</dt><dd>{{ $employee?->employee_code ?? '—' }}</dd></div>
                <div class="kv__row"><dt>ตำแหน่ง</dt><dd>{{ $employee?->position?->title ?? '—' }}</dd></div>
                <div class="kv__row"><dt>แผนก</dt><dd>{{ $employee?->department?->name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>เลขประจำตัวผู้เสียภาษี</dt><dd>{{ format_national_id($employee?->national_id, masked: true) }}</dd></div>
                <div class="kv__row"><dt>วันเริ่มงาน</dt><dd>{{ thai_date($employee?->hire_date) }}</dd></div>
            </dl>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">รายละเอียดการจ่าย <small>หน่วย: บาท</small></h2>
            <div class="pay">
                <table class="doc-table">
                    <thead><tr><th>รายได้</th><th class="num">จำนวนเงิน</th></tr></thead>
                    <tbody>
                        <tr><td>เงินเดือน</td><td class="num">{{ $money($payroll->base_salary) }}</td></tr>
                        <tr><td>เบี้ยเลี้ยง / ค่าตอบแทนอื่น</td><td class="num">{{ $money($payroll->allowances) }}</td></tr>
                    </tbody>
                    <tfoot><tr><td>รวมรายได้</td><td class="num">{{ $money($earnings) }}</td></tr></tfoot>
                </table>

                <table class="doc-table">
                    <thead><tr><th>รายการหัก</th><th class="num">จำนวนเงิน</th></tr></thead>
                    <tbody>
                        <tr><td>ลาไม่รับค่าจ้าง / ขาดงาน และอื่น ๆ</td><td class="num">{{ $money($payroll->deductions) }}</td></tr>
                        <tr class="is-blank"><td>—</td><td></td></tr>
                    </tbody>
                    <tfoot><tr><td>รวมรายการหัก</td><td class="num">{{ $money($payroll->deductions) }}</td></tr></tfoot>
                </table>
            </div>

            <div class="net">
                <div>
                    <div class="net__label">เงินได้สุทธิ</div>
                    <div class="net__words">({{ thai_baht_text($payroll->net_pay) }})</div>
                </div>
                <div class="net__amount">{{ $money($payroll->net_pay) }} <small>บาท</small></div>
            </div>
        </section>

        <section class="doc-section">
            <h2 class="doc-section__title">ยอดสะสมตั้งแต่ต้นปี {{ $payroll->period_year + 543 }} <small>ถึงงวด {{ $period }}</small></h2>
            <div class="doc-stats">
                <div class="doc-stat"><span>รายได้สะสม</span><b>{{ $money($ytd['earnings']) }}</b></div>
                <div class="doc-stat"><span>รายการหักสะสม</span><b>{{ $money($ytd['deductions']) }}</b></div>
                <div class="doc-stat"><span>เงินได้สุทธิสะสม</span><b>{{ $money($ytd['net']) }}</b></div>
                <div class="doc-stat"><span>จำนวนงวด</span><b>{{ $ytd['count'] }} <small>งวด</small></b></div>
            </div>
        </section>

        @if($payroll->note)
            <p class="doc-note"><b>หมายเหตุ:</b> {{ $payroll->note }}</p>
        @endif

        <div class="signs" style="--cols: 2">
            <x-doc.signature role="ผู้จ่ายเงิน" :name="config('hrpro.company.signatory_name')" :position="config('hrpro.company.signatory_title')" />
            <x-doc.signature role="ผู้รับเงิน" :name="$employee?->full_name" />
        </div>

        <x-doc.footer :note="$draft ? 'ฉบับร่าง — สลิปยังไม่ได้รับการยืนยัน ใช้เป็นหลักฐานการจ่ายเงินไม่ได้' : null" />
    </article>
@endsection
