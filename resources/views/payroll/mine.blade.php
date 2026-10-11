@extends('layouts.app')
@section('title', 'สลิปเงินเดือนของฉัน')

@section('content')
    <x-page-header title="สลิปเงินเดือนของฉัน" subtitle="สลิปที่ฝ่ายบุคคลยืนยันแล้ว เปิดดูหรือบันทึกเป็น PDF ได้ทุกเมื่อ" />

    @if(! $employee)
        <x-card><x-empty icon="wallet" title="บัญชีนี้ยังไม่ผูกกับข้อมูลพนักงาน" message="ติดต่อฝ่ายบุคคลเพื่อเชื่อมบัญชีกับทะเบียนพนักงาน" /></x-card>
    @else
        @if($ytd)
            <div class="stats">
                <x-stat :label="'รายได้สะสมปี '.($payslips->first()->period_year + 543)" :value="thb($ytd['earnings'])" icon="wallet" color="green" :meta="$ytd['count'].' งวด'" />
                <x-stat label="ภาษีหัก ณ ที่จ่ายสะสม" :value="thb($ytd['tax'])" icon="file-text" color="amber" meta="ใช้ประกอบการยื่น ภ.ง.ด.90/91" />
                <x-stat label="ประกันสังคมสะสม" :value="thb($ytd['sso'])" icon="shield" color="blue" meta="ส่วนของผู้ประกันตน" />
            </div>
        @endif

        <x-card :padding="false">
            @if($payslips->isEmpty())
                <x-empty icon="wallet" title="ยังไม่มีสลิปเงินเดือน" message="สลิปจะปรากฏที่นี่เมื่อฝ่ายบุคคลยืนยันเงินเดือนของงวดนั้น" />
            @else
                <div class="table-wrap">
                    <table class="table table--cards">
                        <thead>
                            <tr><th>งวด</th><th class="num">รายได้</th><th class="num">หักรวม</th><th class="num">สุทธิ</th><th class="actions"></th></tr>
                        </thead>
                        <tbody>
                            @foreach($payslips as $slip)
                                <tr>
                                    <td class="cell-strong">{{ thai_month($slip->period_month) }} {{ $slip->period_year + 543 }}</td>
                                    <td class="num" data-label="รายได้">{{ thb($slip->earnings) }}</td>
                                    <td class="num" data-label="หักรวม">{{ thb($slip->deductions) }}</td>
                                    <td class="num cell-strong" data-label="สุทธิ">{{ thb($slip->net_pay) }}</td>
                                    <td class="actions">
                                        <x-button :href="route('my-payslips.print', $slip)" variant="ghost" size="sm" icon="printer">เปิดสลิป</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card__pager">{{ $payslips->links() }}</div>
            @endif
        </x-card>
    @endif
@endsection
