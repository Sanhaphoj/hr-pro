@extends('layouts.app')
@section('title', 'เงินเดือน')

@section('content')
    <x-page-header title="เงินเดือน" subtitle="คำนวณและจัดการเงินเดือนรายเดือน">
        <x-slot:actions>
            <x-button :href="route('payroll.print-register', ['year' => $year, 'month' => $month])" variant="secondary" size="sm" icon="printer">พิมพ์รายงานงวดนี้</x-button>
            <a href="{{ route('export.payroll', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">
                <x-icon name="download" width="16" height="16" /> ส่งออก CSV
            </a>
            <a href="{{ route('export.pnd1', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm" title="ใบแนบ ภ.ง.ด.1 สำหรับยื่นกรมสรรพากร">
                <x-icon name="file-text" width="16" height="16" /> ภ.ง.ด.1
            </a>
            <a href="{{ route('export.sso', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm" title="รายละเอียดเงินสมทบ สปส.1-10">
                <x-icon name="file-text" width="16" height="16" /> สปส.1-10
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="no-print">
        <div class="flex items-center justify-between gap-2" style="flex-wrap:wrap;">
            <form method="GET" action="{{ route('payroll.index') }}" class="flex items-center gap-2" style="flex-wrap:wrap;">
                <select name="month" class="input" style="max-width:150px;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($m === $month)>{{ \Illuminate\Support\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
                <input type="number" name="year" value="{{ $year }}" class="input" style="max-width:110px;" min="2000" max="2100">
                <x-button type="submit" variant="secondary" icon="search">ดูงวด</x-button>
            </form>

            @can('payroll.manage')
                <form method="POST" action="{{ route('payroll.generate') }}" data-confirm="คำนวณเงินเดือนของงวด {{ $month }}/{{ $year }} ใช่หรือไม่?">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <x-button type="submit" icon="plus">คำนวณเงินเดือนงวดนี้</x-button>
                </form>
            @endcan
        </div>
    </x-card>

    <div class="stats section-gap">
        <x-stat label="จำนวนสลิป" :value="number_format($totals->cnt)" icon="list" color="blue" :meta="'งวด '.$month.'/'.$year" />
        <x-stat label="ยอดจ่ายสุทธิ" :value="thb($totals->net)" icon="wallet" color="green" meta="รวมทั้งงวด" />
        <x-stat label="ภาษีหัก ณ ที่จ่าย" :value="thb($totals->tax)" icon="file-text" color="amber" meta="นำส่งพร้อม ภ.ง.ด.1" />
        <x-stat label="ประกันสังคม" :value="thb($totals->sso * 2)" icon="shield" color="blue" meta="ลูกจ้าง + นายจ้าง (สปส.1-10)" />
    </div>

    <x-card :padding="false" class="mt-2">
        @if($payrolls->isEmpty())
            <x-empty icon="wallet" title="ยังไม่มีรายการเงินเดือนของงวดนี้" message="กดปุ่ม “คำนวณเงินเดือนงวดนี้” เพื่อสร้างรายการ" />
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>พนักงาน</th>
                            <th class="num">รายได้</th>
                            <th class="num">ภาษี</th>
                            <th class="num">ประกันสังคม</th>
                            <th class="num">หักรวม</th>
                            <th class="num">สุทธิ</th>
                            <th>สถานะ</th>
                            <th class="actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $p)
                            <tr>
                                <td class="cell-strong">{{ $p->employee?->full_name ?? '—' }}<div class="cell-sub">{{ $p->employee?->employee_code }}</div></td>
                                <td class="num" data-label="รายได้">{{ thb($p->earnings) }}@if((float) $p->overtime_pay > 0)<div class="cell-sub">รวม OT</div>@endif</td>
                                <td class="num" data-label="ภาษี">{{ thb($p->withholding_tax) }}</td>
                                <td class="num" data-label="ประกันสังคม">{{ thb($p->social_security) }}</td>
                                <td class="num" data-label="หักรวม">{{ thb($p->deductions) }}</td>
                                <td class="num cell-strong" data-label="สุทธิ">{{ thb($p->net_pay) }}</td>
                                <td data-label="สถานะ">
                                    <x-badge :color="$p->status === 'finalized' ? 'green' : 'gray'">
                                        {{ $p->status === 'finalized' ? 'ยืนยันแล้ว' : 'ฉบับร่าง' }}
                                    </x-badge>
                                </td>
                                <td class="actions">
                                    <x-button href="{{ route('payroll.show', $p) }}" variant="ghost" size="sm" icon="eye">สลิป</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card__pager">{{ $payrolls->links() }}</div>
        @endif
    </x-card>
@endsection
