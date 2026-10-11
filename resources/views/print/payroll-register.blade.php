@extends('layouts.print')

@php
    $period = thai_month($month).' '.($year + 543);
    $money = fn ($value) => number_format((float) $value, 2);
@endphp

@section('orientation', 'landscape')
@section('title', 'รายงานสรุปเงินเดือน · '.$period)
@section('filename', sprintf('รายงานเงินเดือน_%d-%02d', $year + 543, $month))
@section('back', route('payroll.index', ['year' => $year, 'month' => $month]))

@section('content')
    <article class="sheet sheet--landscape">
        <x-doc.letterhead title="รายงานสรุปเงินเดือน" subtitle="Payroll register"
                          :meta="['เลขที่' => sprintf('PRG-%d%02d', $year + 543, $month), 'งวด' => $period]" />

        @if($payrolls->isEmpty())
            <div class="doc-empty">ยังไม่มีรายการเงินเดือนของงวด {{ $period }}</div>
        @else
            <div class="doc-stats">
                <div class="doc-stat"><span>จำนวนพนักงาน</span><b>{{ number_format($payrolls->count()) }} <small>คน</small></b></div>
                <div class="doc-stat"><span>รวมเงินเดือน</span><b>{{ $money($totals['base']) }}</b></div>
                <div class="doc-stat"><span>รวมเบี้ยเลี้ยง</span><b>{{ $money($totals['allowances']) }}</b></div>
                <div class="doc-stat"><span>รวมรายการหัก</span><b>{{ $money($totals['deductions']) }}</b></div>
                <div class="doc-stat"><span>รวมจ่ายสุทธิ</span><b>{{ $money($totals['net']) }}</b></div>
                <div class="doc-stat"><span>ยืนยันแล้ว</span><b>{{ $totals['finalized'] }} <small>/ {{ $payrolls->count() }}</small></b></div>
            </div>

            <div class="doc-table-wrap">
                <table class="doc-table doc-table--compact doc-table--dense">
                    <thead>
                        <tr>
                            <th class="center">ลำดับ</th>
                            <th>รหัส</th>
                            <th>ชื่อ–สกุล</th>
                            <th>แผนก</th>
                            <th>ตำแหน่ง</th>
                            <th class="num">เงินเดือน</th>
                            <th class="num">เบี้ยเลี้ยง</th>
                            <th class="num">รายการหัก</th>
                            <th class="num">สุทธิ</th>
                            <th class="center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $i => $row)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td class="nowrap">{{ $row->employee?->employee_code ?? '—' }}</td>
                                <td>{{ $row->employee?->full_name ?? '—' }}</td>
                                <td>{{ $row->employee?->department?->name ?? '—' }}</td>
                                <td>{{ $row->employee?->position?->title ?? '—' }}</td>
                                <td class="num">{{ $money($row->base_salary) }}</td>
                                <td class="num">{{ $money($row->allowances) }}</td>
                                <td class="num">{{ $money($row->deductions) }}</td>
                                <td class="num"><b>{{ $money($row->net_pay) }}</b></td>
                                <td class="center">{{ $row->status === 'finalized' ? 'ยืนยันแล้ว' : 'ฉบับร่าง' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">รวมทั้งสิ้น {{ number_format($payrolls->count()) }} รายการ</td>
                            <td class="num">{{ $money($totals['base']) }}</td>
                            <td class="num">{{ $money($totals['allowances']) }}</td>
                            <td class="num">{{ $money($totals['deductions']) }}</td>
                            <td class="num">{{ $money($totals['net']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="doc-note"><b>ยอดจ่ายสุทธิรวม</b> {{ $money($totals['net']) }} บาท ({{ thai_baht_text($totals['net']) }})</p>
        @endif

        <div class="signs">
            <x-doc.signature role="ผู้จัดทำ" />
            <x-doc.signature role="ผู้ตรวจสอบ" />
            <x-doc.signature role="ผู้อนุมัติ" />
        </div>

        <x-doc.footer note="เอกสารลับ — สำหรับฝ่ายบุคคลและผู้มีอำนาจอนุมัติเท่านั้น" />
    </article>
@endsection
