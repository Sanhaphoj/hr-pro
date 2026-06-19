@extends('layouts.app')
@section('title', $employee->full_name)

@section('content')
    <x-page-header :title="$employee->full_name" :subtitle="($employee->position?->title ?? 'ยังไม่ระบุตำแหน่ง').' · '.($employee->department?->name ?? 'ยังไม่ระบุแผนก')">
        <x-slot:actions>
            <x-button href="{{ route('employees.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
            @can('employees.update')
                <x-button href="{{ route('employees.edit', $employee) }}" icon="edit">แก้ไข</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid--2">
        <div class="grid" style="align-content:start;">
            <x-card title="ข้อมูลพนักงาน">
                <div class="flex items-center gap-2" style="margin-bottom:16px;">
                    <span class="avatar avatar--lg" style="background: {{ avatar_color($employee->email) }}">{{ $employee->initials }}</span>
                    <div>
                        <div class="cell-strong" style="font-size:16px;">{{ $employee->full_name }}</div>
                        <div class="cell-sub">{{ $employee->employee_code }}</div>
                        <div class="flex items-center gap-1" style="margin-top:6px;">
                            <x-status-badge type="employee" :value="$employee->status" />
                            <x-status-badge type="employment" :value="$employee->employment_type" />
                        </div>
                    </div>
                </div>

                <dl class="dl">
                    <dt>อีเมล</dt><dd>{{ $employee->email }}</dd>
                    <dt>เบอร์โทรศัพท์</dt><dd>{{ $employee->phone ?? '—' }}</dd>
                    <dt>เลขบัตรประชาชน</dt><dd>{{ $employee->national_id ?? '—' }}</dd>
                    <dt>วันเกิด</dt><dd>{{ $employee->date_of_birth?->translatedFormat('j F Y') ?? '—' }}</dd>
                    <dt>เพศ</dt><dd>{{ ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'อื่น ๆ'][$employee->gender] ?? '—' }}</dd>
                    <dt>แผนก</dt><dd>{{ $employee->department?->name ?? '—' }}</dd>
                    <dt>ตำแหน่ง</dt><dd>{{ $employee->position?->title ?? '—' }}</dd>
                    <dt>หัวหน้างาน</dt><dd>{{ $employee->manager?->full_name ?? '—' }}</dd>
                    <dt>วันเริ่มงาน</dt><dd>{{ $employee->hire_date?->translatedFormat('j F Y') ?? '—' }}</dd>
                    <dt>วันสิ้นสุดการจ้าง</dt><dd>{{ $employee->termination_date?->translatedFormat('j F Y') ?? '—' }}</dd>
                    <dt>เงินเดือน</dt><dd>{{ $employee->base_salary !== null ? thb($employee->base_salary) : '—' }}</dd>
                    <dt>ที่อยู่</dt><dd>{{ $employee->address ?? '—' }}</dd>
                    <dt>ผู้ติดต่อฉุกเฉิน</dt><dd>{{ $employee->emergency_contact_name ?? '—' }}</dd>
                    <dt>เบอร์ผู้ติดต่อฉุกเฉิน</dt><dd>{{ $employee->emergency_contact_phone ?? '—' }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="grid" style="align-content:start;">
            <x-card title="วันลาคงเหลือ ปี {{ $year + 543 }}">
                @if($balances->isEmpty())
                    <x-empty icon="calendar" title="ยังไม่มีข้อมูลวันลา" message="ระบบจะสร้างยอดวันลาเมื่อมีการยื่นคำขอลา" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ประเภทการลา</th>
                                    <th style="text-align:right;">สิทธิ์</th>
                                    <th style="text-align:right;">ใช้ไป</th>
                                    <th style="text-align:right;">รออนุมัติ</th>
                                    <th style="text-align:right;">คงเหลือ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($balances as $balance)
                                    <tr>
                                        <td class="cell-strong">{{ $balance->leaveType?->name ?? '—' }}</td>
                                        <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $balance->entitled_days, 1), '0'), '.') }}</td>
                                        <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $balance->used_days, 1), '0'), '.') }}</td>
                                        <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $balance->pending_days, 1), '0'), '.') }}</td>
                                        <td style="text-align:right;" class="cell-strong">{{ rtrim(rtrim(number_format($balance->remaining_days, 1), '0'), '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <x-card title="คำขอลาล่าสุด">
                @if($leaveRequests->isEmpty())
                    <x-empty icon="inbox" title="ยังไม่มีคำขอลา" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ประเภท</th>
                                    <th>ช่วงวันที่</th>
                                    <th style="text-align:right;">จำนวนวัน</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaveRequests as $request)
                                    <tr>
                                        <td class="cell-strong">{{ $request->leaveType?->name ?? '—' }}</td>
                                        <td class="cell-sub">{{ $request->start_date->format('d/m/Y') }} – {{ $request->end_date->format('d/m/Y') }}</td>
                                        <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $request->total_days, 1), '0'), '.') }}</td>
                                        <td><x-status-badge type="leave" :value="$request->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
