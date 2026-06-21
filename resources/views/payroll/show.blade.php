@extends('layouts.app')
@section('title', 'สลิปเงินเดือน')

@section('content')
    <x-page-header :title="'สลิปเงินเดือน · '.$payroll->period_label" :subtitle="$payroll->employee?->full_name">
        <x-slot:breadcrumb>
            <a href="{{ route('payroll.index', ['year' => $payroll->period_year, 'month' => $payroll->period_month]) }}">← กลับรายการเงินเดือน</a>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <button type="button" class="btn btn--secondary btn--sm" onclick="window.print()"><x-icon name="download" width="16" height="16" /> พิมพ์ / บันทึก PDF</button>
            @can('payroll.manage')
                @if($payroll->status !== 'finalized')
                    <form method="POST" action="{{ route('payroll.finalize', $payroll) }}">
                        @csrf
                        <x-button type="submit" icon="check">ยืนยันสลิป</x-button>
                    </form>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div style="max-width:640px;">
        <x-card>
            <div class="flex items-center justify-between" style="margin-bottom:16px;">
                <div>
                    <div class="cell-strong" style="font-size:16px;">{{ config('hrpro.company_name') }}</div>
                    <div class="cell-sub">สลิปเงินเดือนงวด {{ $payroll->period_label }}</div>
                </div>
                <x-badge :color="$payroll->status === 'finalized' ? 'green' : 'gray'">
                    {{ $payroll->status === 'finalized' ? 'ยืนยันแล้ว' : 'ฉบับร่าง' }}
                </x-badge>
            </div>

            <dl class="dl">
                <dt>พนักงาน</dt><dd>{{ $payroll->employee?->full_name ?? '—' }}</dd>
                <dt>รหัสพนักงาน</dt><dd>{{ $payroll->employee?->employee_code ?? '—' }}</dd>
                <dt>แผนก</dt><dd>{{ $payroll->employee?->department?->name ?? '—' }}</dd>
                <dt>ตำแหน่ง</dt><dd>{{ $payroll->employee?->position?->title ?? '—' }}</dd>
            </dl>

            <table class="table" style="margin-top:16px;">
                <tbody>
                    <tr><td>เงินเดือนฐาน</td><td class="num">{{ thb($payroll->base_salary) }}</td></tr>
                    <tr><td>เบี้ยเลี้ยง / ค่าตอบแทน</td><td class="num">{{ thb($payroll->allowances) }}</td></tr>
                    <tr><td>รายการหัก</td><td class="num" style="color:var(--c-danger);">- {{ thb($payroll->deductions) }}</td></tr>
                    <tr><td class="cell-strong">เงินเดือนสุทธิ</td><td class="num cell-strong" style="font-size:16px;">{{ thb($payroll->net_pay) }}</td></tr>
                </tbody>
            </table>

            @if($payroll->note)
                <p class="cell-sub" style="margin-top:12px;">หมายเหตุ: {{ $payroll->note }}</p>
            @endif
        </x-card>
    </div>
@endsection
