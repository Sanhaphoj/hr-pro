@extends('layouts.app')
@section('title', 'อนุมัติการลา')

@php
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
@endphp

@section('content')
    <x-page-header title="อนุมัติการลา" subtitle="คำขอลาที่รอการพิจารณา" />

    <x-card :padding="false">
        @if($requests->isEmpty())
            <x-empty icon="check-circle" title="ไม่มีคำขอที่รออนุมัติ"
                     message="คำขอลาทั้งหมดได้รับการดำเนินการแล้ว" />
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>พนักงาน</th>
                            <th>ประเภทการลา</th>
                            <th>ช่วงวันที่</th>
                            <th>จำนวนวัน</th>
                            <th>เหตุผล</th>
                            <th class="actions">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                            <tr>
                                <td>
                                    <a href="{{ route('leave-requests.show', $req) }}" class="cell-strong">
                                        {{ $req->employee?->full_name ?? '—' }}
                                    </a>
                                    <div class="cell-sub">{{ $req->employee?->department?->name ?? '—' }}</div>
                                </td>
                                <td data-label="ประเภทการลา">{{ $req->leaveType?->name ?? '—' }}</td>
                                <td class="cell-sub" data-label="ช่วงวันที่">{{ $req->start_date->format('d/m/Y') }} – {{ $req->end_date->format('d/m/Y') }}</td>
                                <td data-label="จำนวนวัน">{{ $fmt($req->total_days) }} วัน</td>
                                <td class="cell-sub" data-label="เหตุผล">{{ \Illuminate\Support\Str::limit($req->reason, 50) }}</td>
                                <td class="actions">
                                    <div class="flex items-center gap-2 justify-end flex-wrap">
                                        <form method="POST" action="{{ route('leave-approvals.approve', $req) }}"
                                              data-confirm="ยืนยันการอนุมัติคำขอลานี้?">
                                            @csrf
                                            <x-button type="submit" variant="success" size="sm" icon="check">อนุมัติ</x-button>
                                        </form>

                                        <details>
                                            <summary class="btn btn--danger btn--sm" style="cursor:pointer;">
                                                <x-icon name="x" width="16" height="16" />ปฏิเสธ
                                            </summary>
                                            <form method="POST" action="{{ route('leave-approvals.reject', $req) }}" class="reject-form">
                                                @csrf
                                                <x-textarea name="reason" label="เหตุผลที่ปฏิเสธ" :required="true" :rows="3"
                                                            hint="ไม่เกิน 500 ตัวอักษร" />
                                                <div class="mt-1">
                                                    <x-button type="submit" variant="danger" size="sm" icon="x">ยืนยันการปฏิเสธ</x-button>
                                                </div>
                                            </form>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card__pager">{{ $requests->links() }}</div>
        @endif
    </x-card>
@endsection
