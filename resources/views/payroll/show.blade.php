@extends('layouts.app')
@section('title', 'สลิปเงินเดือน')

@php
    $draft = ! $payroll->isFinalized();
    $lines = $payroll->deductionLines();
@endphp

@section('content')
    <x-page-header :title="'สลิปเงินเดือน · '.$payroll->period_label" :subtitle="$payroll->employee?->full_name">
        <x-slot:breadcrumb>
            <a href="{{ route('payroll.index', ['year' => $payroll->period_year, 'month' => $payroll->period_month]) }}">← กลับรายการเงินเดือน</a>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <x-button :href="route('payroll.print', $payroll)" variant="secondary" size="sm" icon="printer">พิมพ์ / บันทึก PDF</x-button>
            @can('payroll.manage')
                @if($draft)
                    <form method="POST" action="{{ route('payroll.finalize', $payroll) }}" data-confirm="ยืนยันสลิปนี้แล้วจะแก้ไขไม่ได้ และพนักงานจะเห็นสลิปในหน้าของตนเอง ยืนยันหรือไม่?">
                        @csrf
                        <x-button type="submit" icon="check">ยืนยันสลิป</x-button>
                    </form>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid--2">
        <x-card>
            <div class="flex items-center justify-between mb-2">
                <div>
                    <div class="cell-strong" style="font-size:16px;">{{ config('hrpro.company_name') }}</div>
                    <div class="cell-sub">สลิปเงินเดือนงวด {{ $payroll->period_label }}</div>
                </div>
                <x-badge :color="$draft ? 'gray' : 'green'">{{ $draft ? 'ฉบับร่าง' : 'ยืนยันแล้ว' }}</x-badge>
            </div>

            <dl class="dl">
                <dt>พนักงาน</dt><dd>{{ $payroll->employee?->full_name ?? '—' }}</dd>
                <dt>รหัสพนักงาน</dt><dd>{{ $payroll->employee?->employee_code ?? '—' }}</dd>
                <dt>แผนก</dt><dd>{{ $payroll->employee?->department?->name ?? '—' }}</dd>
                <dt>ตำแหน่ง</dt><dd>{{ $payroll->employee?->position?->title ?? '—' }}</dd>
            </dl>

            <div class="payslip section-gap">
                <table class="table">
                    <thead><tr><th>รายได้</th><th class="num">บาท</th></tr></thead>
                    <tbody>
                        <tr><td>เงินเดือน</td><td class="num">{{ thb($payroll->base_salary) }}</td></tr>
                        @if((float) $payroll->overtime_pay > 0)
                            <tr><td>ค่าล่วงเวลา <span class="cell-sub">({{ rtrim(rtrim(number_format((float) $payroll->overtime_hours, 2), '0'), '.') }} ชม. × {{ config('hrpro.payroll.overtime_rate', 1.5) }} เท่า)</span></td><td class="num">{{ thb($payroll->overtime_pay) }}</td></tr>
                        @endif
                        @if((float) $payroll->allowances > 0)
                            <tr><td>เบี้ยเลี้ยง / ค่าตอบแทนอื่น</td><td class="num">{{ thb($payroll->allowances) }}</td></tr>
                        @endif
                        <tr class="payslip__total"><td>รวมรายได้</td><td class="num">{{ thb($payroll->earnings) }}</td></tr>
                    </tbody>
                </table>
                <table class="table">
                    <thead><tr><th>รายการหัก</th><th class="num">บาท</th></tr></thead>
                    <tbody>
                        @forelse($lines as $label => $amount)
                            <tr><td>{{ $label }}</td><td class="num">{{ thb($amount) }}</td></tr>
                        @empty
                            <tr><td class="cell-sub">ไม่มีรายการหัก</td><td></td></tr>
                        @endforelse
                        <tr class="payslip__total"><td>รวมรายการหัก</td><td class="num">{{ thb($payroll->deductions) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="payslip__net">
                <span>เงินได้สุทธิ</span>
                <b>{{ thb($payroll->net_pay) }}</b>
            </div>

            @if($payroll->note)
                <p class="cell-sub field-gap">หมายเหตุ: {{ $payroll->note }}</p>
            @endif
        </x-card>

        <div class="grid content-start">
            @can('payroll.manage')
                @if($draft)
                    <x-card title="ปรับรายได้งวดนี้" subtitle="ระบบคำนวณประกันสังคม ภาษี และยอดสุทธิใหม่ให้อัตโนมัติ">
                        <form method="POST" action="{{ route('payroll.update', $payroll) }}" class="grid" style="gap:14px;">
                            @csrf
                            @method('PUT')
                            <x-input name="overtime_hours" type="number" step="0.5" min="0" max="300" label="ชั่วโมงทำงานล่วงเวลา (OT)"
                                     :value="old('overtime_hours', (float) $payroll->overtime_hours)"
                                     :hint="'ค่าแรงต่อชั่วโมง '.thb(app(\App\Services\PayrollService::class)->dailyRate((float) $payroll->base_salary) / max(1, (float) config('hrpro.payroll.hours_per_day', 8))).' × '.config('hrpro.payroll.overtime_rate', 1.5).' เท่า'" />
                            <x-input name="allowances" type="number" step="0.01" min="0" label="เบี้ยเลี้ยง / ค่าตอบแทนอื่น (บาท)"
                                     :value="old('allowances', (float) $payroll->allowances)" />
                            <div><x-button type="submit" icon="refresh">คำนวณใหม่</x-button></div>
                        </form>
                    </x-card>
                @endif
            @endcan

            <x-card title="ที่มาของภาษีหัก ณ ที่จ่าย">
                <p class="cell-sub" style="margin:0;">
                    ประมาณการแบบเฉลี่ยทั้งปี: เงินได้งวดนี้ × 12 หักค่าใช้จ่าย 50% (ไม่เกิน 100,000 บาท)
                    ค่าลดหย่อนส่วนตัว {{ number_format((float) config('hrpro.payroll.withholding_tax.personal_allowance', 60000)) }} บาท
                    ประกันสังคม และกองทุนสำรองเลี้ยงชีพ แล้วคิดภาษีอัตราก้าวหน้า หาร 12
                    ค่าลดหย่อนอื่น (คู่สมรส บุตร ประกันชีวิต ฯลฯ) พนักงานใช้สิทธิ์ตอนยื่นแบบ ภ.ง.ด.90/91 เอง
                </p>
            </x-card>
        </div>
    </div>
@endsection
