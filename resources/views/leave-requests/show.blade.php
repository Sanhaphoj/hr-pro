@extends('layouts.app')
@section('title', 'รายละเอียดคำขอลา')

@php
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $cancellable = in_array($leaveRequest->status, [\App\Models\LeaveRequest::STATUS_PENDING, \App\Models\LeaveRequest::STATUS_APPROVED], true);
@endphp

@section('content')
    <x-page-header title="รายละเอียดคำขอลา" subtitle="คำขอของ {{ $leaveRequest->employee?->full_name ?? '—' }}">
        <x-slot:actions>
            <x-button :href="route('leave-requests.index')" variant="ghost" icon="arrow-left">กลับ</x-button>
            @if($cancellable && $isOwner)
                <form method="POST" action="{{ route('leave-requests.cancel', $leaveRequest) }}"
                      data-confirm="ยืนยันการยกเลิกคำขอลานี้?">
                    @csrf
                    <x-button type="submit" variant="danger" icon="x">ยกเลิกคำขอ</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid--halves">
        <x-card title="ข้อมูลการลา">
            <dl class="dl">
                <dt>พนักงาน</dt><dd>{{ $leaveRequest->employee?->full_name ?? '—' }}</dd>
                <dt>แผนก</dt><dd>{{ $leaveRequest->employee?->department?->name ?? '—' }}</dd>
                <dt>ประเภทการลา</dt><dd>{{ $leaveRequest->leaveType?->name ?? '—' }}</dd>
                <dt>วันที่เริ่มลา</dt><dd>{{ $leaveRequest->start_date->translatedFormat('j F Y') }}</dd>
                <dt>วันที่สิ้นสุด</dt><dd>{{ $leaveRequest->end_date->translatedFormat('j F Y') }}</dd>
                <dt>จำนวนวัน</dt><dd>{{ $fmt($leaveRequest->total_days) }} วัน</dd>
                <dt>สถานะ</dt><dd><x-status-badge type="leave" :value="$leaveRequest->status" /></dd>
            </dl>

            <div style="margin-top:16px;">
                <div class="cell-sub" style="margin-bottom:4px;">เหตุผลการลา</div>
                <div>{{ $leaveRequest->reason ?: '—' }}</div>
            </div>

            @if($leaveRequest->rejection_reason)
                <div style="margin-top:16px;">
                    <div class="cell-sub" style="margin-bottom:4px;">เหตุผลที่ไม่อนุมัติ</div>
                    <div>{{ $leaveRequest->rejection_reason }}</div>
                </div>
            @endif

            @if($leaveRequest->attachment_path)
                <div style="margin-top:16px;">
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($leaveRequest->attachment_path) }}"
                       target="_blank" rel="noopener" class="btn btn--ghost btn--sm">
                        <x-icon name="download" width="16" height="16" />ดูเอกสารแนบ
                    </a>
                </div>
            @endif
        </x-card>

        <x-card title="สถานะการดำเนินการ">
            <ul class="timeline">
                <li>
                    <div class="cell-strong">ส่งคำขอ</div>
                    <div class="cell-sub">{{ $leaveRequest->created_at->translatedFormat('j F Y H:i') }} น.</div>
                </li>

                @if($leaveRequest->status === \App\Models\LeaveRequest::STATUS_APPROVED)
                    <li>
                        <div class="cell-strong">อนุมัติแล้ว</div>
                        <div class="cell-sub">
                            โดย {{ $leaveRequest->approver?->name ?? 'ระบบ' }}
                            @if($leaveRequest->approved_at)
                                · {{ $leaveRequest->approved_at->translatedFormat('j F Y H:i') }} น.
                            @endif
                        </div>
                    </li>
                @elseif($leaveRequest->status === \App\Models\LeaveRequest::STATUS_REJECTED)
                    <li>
                        <div class="cell-strong">ไม่อนุมัติ</div>
                        <div class="cell-sub">
                            โดย {{ $leaveRequest->approver?->name ?? '—' }}
                            @if($leaveRequest->approved_at)
                                · {{ $leaveRequest->approved_at->translatedFormat('j F Y H:i') }} น.
                            @endif
                        </div>
                    </li>
                @elseif($leaveRequest->status === \App\Models\LeaveRequest::STATUS_CANCELLED)
                    <li>
                        <div class="cell-strong">ยกเลิกคำขอ</div>
                        <div class="cell-sub">คำขอนี้ถูกยกเลิกแล้ว</div>
                    </li>
                @else
                    <li>
                        <div class="cell-strong">รออนุมัติ</div>
                        <div class="cell-sub">คำขอกำลังรอการพิจารณาจากผู้อนุมัติ</div>
                    </li>
                @endif
            </ul>
        </x-card>
    </div>
@endsection
