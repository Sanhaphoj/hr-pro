@extends('layouts.app')
@section('title', 'คำขอลางาน')

@section('content')
    <x-page-header title="คำขอลางาน" subtitle="{{ $viewAll ? 'คำขอลาทั้งหมดในระบบ' : 'ประวัติการขอลาของคุณ' }}">
        @if($employee)
            <x-slot:actions>
                <x-button :href="route('leave-requests.create')" icon="plus">ขอลางาน</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if(! $employee && ! $viewAll)
        <x-card :padding="false">
            <x-empty icon="user" title="ยังไม่มีโปรไฟล์พนักงาน"
                     message="ยังไม่มีโปรไฟล์พนักงานที่เชื่อมโยงกับบัญชีของคุณ" />
        </x-card>
    @else
        <x-card :padding="false">
            <div class="toolbar" style="padding:16px 20px;">
                <form method="GET" action="{{ route('leave-requests.index') }}" class="toolbar">
                    <x-select name="status" :options="$statuses" :selected="$status"
                              placeholder="ทุกสถานะ" />
                    <x-button type="submit" variant="secondary" icon="filter">กรอง</x-button>
                    @if($status)
                        <x-button :href="route('leave-requests.index')" variant="ghost">ล้าง</x-button>
                    @endif
                </form>
            </div>

            @if($requests->isEmpty())
                <x-empty icon="calendar" title="ยังไม่มีคำขอลา"
                         message="{{ $employee ? 'เริ่มต้นด้วยการกดปุ่ม “ขอลางาน”' : 'ยังไม่มีคำขอลาในระบบ' }}" />
            @else
                <div class="table-wrap">
                    <table class="table table--cards">
                        <thead>
                            <tr>
                                @if($viewAll)<th>พนักงาน</th>@endif
                                <th>ประเภทการลา</th>
                                <th>ช่วงวันที่</th>
                                <th>จำนวนวัน</th>
                                <th>สถานะ</th>
                                <th class="actions">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $req)
                                @php
                                    $owner = $req->employee && $req->employee->user_id === auth()->id();
                                    $cancellable = in_array($req->status, [\App\Models\LeaveRequest::STATUS_PENDING, \App\Models\LeaveRequest::STATUS_APPROVED], true);
                                @endphp
                                <tr>
                                    @if($viewAll)
                                        <td class="cell-strong">{{ $req->employee?->full_name ?? '—' }}</td>
                                    @endif
                                    <td data-label="ประเภทการลา">{{ $req->leaveType?->name ?? '—' }}</td>
                                    <td class="cell-sub" data-label="ช่วงวันที่">{{ $req->start_date->format('d/m/Y') }} – {{ $req->end_date->format('d/m/Y') }}</td>
                                    <td data-label="จำนวนวัน">{{ rtrim(rtrim(number_format((float) $req->total_days, 1), '0'), '.') }} วัน</td>
                                    <td data-label="สถานะ"><x-status-badge type="leave" :value="$req->status" /></td>
                                    <td>
                                        <div class="flex items-center gap-2 justify-end">
                                            <a href="{{ route('leave-requests.show', $req) }}" class="btn btn--ghost btn--sm">
                                                <x-icon name="eye" width="16" height="16" />ดู
                                            </a>
                                            @if($cancellable && ($owner || $viewAll))
                                                <form method="POST" action="{{ route('leave-requests.cancel', $req) }}"
                                                      data-confirm="ยืนยันการยกเลิกคำขอลานี้?">
                                                    @csrf
                                                    <x-button type="submit" variant="danger" size="sm" icon="x">ยกเลิก</x-button>
                                                </form>
                                            @endif
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
    @endif
@endsection
