@extends('layouts.app')
@section('title', 'ประเมินผล')

@section('content')
    <x-page-header title="ประเมินผลการปฏิบัติงาน" subtitle="บันทึกและติดตามผลการประเมินพนักงาน">
        @can('performance.manage')
            <x-slot:actions>
                <x-button href="{{ route('performance.create') }}" icon="plus">เพิ่มการประเมิน</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        @if($reviews->isEmpty())
            <x-empty icon="chart" title="ยังไม่มีผลการประเมิน" message="เริ่มต้นด้วยการบันทึกการประเมินแรก" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>พนักงาน</th>
                            <th>งวด</th>
                            <th>คะแนน</th>
                            <th>ผู้ประเมิน</th>
                            <th>สถานะ</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                            <tr>
                                <td class="cell-strong">{{ $review->employee?->full_name ?? '—' }}</td>
                                <td>{{ $review->period }}</td>
                                <td><x-badge :color="$review->score >= 4 ? 'green' : ($review->score >= 3 ? 'amber' : 'red')">{{ $review->score }}/5</x-badge></td>
                                <td class="cell-sub">{{ $review->reviewer?->name ?? '—' }}</td>
                                <td><x-badge :color="$review->status === 'submitted' ? 'green' : 'gray'">{{ $review->status === 'submitted' ? 'ส่งแล้ว' : 'ฉบับร่าง' }}</x-badge></td>
                                <td style="text-align:right;">
                                    <x-button href="{{ route('performance.show', $review) }}" variant="ghost" size="sm" icon="eye">ดู</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $reviews->links() }}</div>
        @endif
    </x-card>
@endsection
