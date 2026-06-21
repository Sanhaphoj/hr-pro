@extends('layouts.app')
@section('title', 'เงินเดือน')

@section('content')
    <x-page-header title="เงินเดือน" subtitle="คำนวณและจัดการเงินเดือนรายเดือน">
        <x-slot:actions>
            <a href="{{ route('export.payroll', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">
                <x-icon name="download" width="16" height="16" /> ส่งออก CSV
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
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

    <div class="stats" style="margin-top:18px;">
        <x-stat label="จำนวนสลิป" :value="number_format($totals->cnt)" icon="list" color="blue" :meta="'งวด '.$month.'/'.$year" />
        <x-stat label="ยอดจ่ายสุทธิ" :value="thb($totals->net)" icon="wallet" color="green" meta="รวมทั้งงวด" />
        <x-stat label="ยอดหักรวม" :value="thb($totals->ded)" icon="filter" color="amber" meta="ลาไม่รับค่าจ้าง ฯลฯ" />
    </div>

    <x-card :padding="false" class="mt-2">
        @if($payrolls->isEmpty())
            <x-empty icon="wallet" title="ยังไม่มีรายการเงินเดือนของงวดนี้" message="กดปุ่ม “คำนวณเงินเดือนงวดนี้” เพื่อสร้างรายการ" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>พนักงาน</th>
                            <th class="num">เงินเดือนฐาน</th>
                            <th class="num">หัก</th>
                            <th class="num">สุทธิ</th>
                            <th>สถานะ</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $p)
                            <tr>
                                <td class="cell-strong">{{ $p->employee?->full_name ?? '—' }}<div class="cell-sub">{{ $p->employee?->employee_code }}</div></td>
                                <td class="num">{{ thb($p->base_salary) }}</td>
                                <td class="num">{{ thb($p->deductions) }}</td>
                                <td class="num cell-strong">{{ thb($p->net_pay) }}</td>
                                <td>
                                    <x-badge :color="$p->status === 'finalized' ? 'green' : 'gray'">
                                        {{ $p->status === 'finalized' ? 'ยืนยันแล้ว' : 'ฉบับร่าง' }}
                                    </x-badge>
                                </td>
                                <td style="text-align:right;">
                                    <x-button href="{{ route('payroll.show', $p) }}" variant="ghost" size="sm" icon="eye">สลิป</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $payrolls->links() }}</div>
        @endif
    </x-card>
@endsection
