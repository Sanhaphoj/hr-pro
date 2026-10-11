@extends('layouts.print')

@php
    use App\Models\LeaveRequest;

    $employee = $leaveRequest->employee;
    $typeName = $leaveRequest->leaveType?->name ?? 'ลา';
    // "ลาป่วย" reads "ขอลาป่วย"; a type named without the verb gets it added.
    $subject = str_starts_with($typeName, 'ลา') ? 'ขอ'.$typeName : 'ขอลา'.$typeName;
    $days = fn ($value) => rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
    $status = $leaveRequest->status;
    $statusLabels = [
        LeaveRequest::STATUS_PENDING => 'รออนุมัติ',
        LeaveRequest::STATUS_APPROVED => 'อนุมัติแล้ว',
        LeaveRequest::STATUS_REJECTED => 'ไม่อนุมัติ',
        LeaveRequest::STATUS_CANCELLED => 'ยกเลิก',
    ];
    $year = $leaveRequest->start_date->year;
    $entitled = $balance ? (float) $balance->entitled_days : (float) ($leaveRequest->leaveType?->days_per_year ?? 0);
    $thisTime = (float) $leaveRequest->total_days;
    $decided = in_array($status, [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED], true);
    $approverName = $leaveRequest->approver?->name
        ?? ($status === LeaveRequest::STATUS_APPROVED ? 'อนุมัติอัตโนมัติโดยระบบ' : null);
@endphp

@section('title', 'ใบลา · '.($employee?->full_name ?? '—'))
@section('filename', sprintf('ใบลา_%s_%s', $employee?->employee_code ?? $leaveRequest->id, $leaveRequest->start_date->format('Y-m-d')))
@section('back', route('leave-requests.show', $leaveRequest))

@section('content')
    <article class="sheet">
        @if($status === LeaveRequest::STATUS_CANCELLED)
            <div class="doc-watermark" aria-hidden="true"><span>ยกเลิก</span></div>
        @endif

        <x-doc.letterhead title="ใบขออนุญาตลา" subtitle="Leave request"
                          :meta="['เลขที่' => doc_no('LV', $leaveRequest->id, $leaveRequest->created_at), 'สถานะ' => $statusLabels[$status] ?? $status]" />

        <div class="letter-meta">
            <span></span>
            <div class="letter-meta__right">
                เขียนที่ {{ config('hrpro.company.name') }}<br>
                วันที่ {{ thai_date($leaveRequest->created_at) }}
            </div>
        </div>

        <div class="letter">
            <p class="flush"><b>เรื่อง</b>&nbsp; {{ $subject }}</p>
            <p class="flush"><b>เรียน</b>&nbsp; {{ $employee?->manager?->full_name ?? 'ผู้บังคับบัญชา' }}</p>
            <p>
                ข้าพเจ้า <span class="fill">{{ $employee?->full_name ?? '—' }}</span>
                รหัสพนักงาน <span class="fill">{{ $employee?->employee_code ?? '—' }}</span>
                ตำแหน่ง <span class="fill">{{ $employee?->position?->title ?? '—' }}</span>
                สังกัดแผนก <span class="fill">{{ $employee?->department?->name ?? '—' }}</span>
                มีความประสงค์{{ $subject }} เนื่องจาก <span class="fill">{{ $leaveRequest->reason ?: '—' }}</span>
                ตั้งแต่วันที่ <span class="fill">{{ thai_date($leaveRequest->start_date) }}</span>
                ถึงวันที่ <span class="fill">{{ thai_date($leaveRequest->end_date) }}</span>
                มีกำหนด <span class="fill">{{ $days($thisTime) }}</span> วันทำการ
                @if($employee?->phone)
                    ในระหว่างการลาติดต่อข้าพเจ้าได้ที่หมายเลข <span class="fill">{{ $employee->phone }}</span>
                @endif
            </p>
            @if($leaveRequest->attachment_path)
                <p class="flush">พร้อมนี้ได้แนบเอกสารประกอบการลาไว้ในระบบแล้ว</p>
            @endif
        </div>

        <div class="signs signs--end">
            <x-doc.signature role="ผู้ขอลา" :name="$employee?->full_name" :date="thai_date($leaveRequest->created_at, 'numeric')" />
        </div>

        <section class="doc-section">
            <h2 class="doc-section__title">สถิติการลาประเภทนี้ในปี {{ $year + 543 }} <small>หน่วย: วันทำการ</small></h2>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>ประเภทการลา</th>
                        <th class="num">สิทธิ์ทั้งปี</th>
                        <th class="num">ลามาแล้ว</th>
                        <th class="num">ลาครั้งนี้</th>
                        <th class="num">รวมเป็น</th>
                        <th class="num">คงเหลือ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $typeName }}</td>
                        <td class="num">{{ $entitled > 0 ? $days($entitled) : 'ไม่จำกัด' }}</td>
                        <td class="num">{{ $days($takenBefore) }}</td>
                        <td class="num">{{ $days($thisTime) }}</td>
                        <td class="num"><b>{{ $days($takenBefore + $thisTime) }}</b></td>
                        <td class="num">{{ $entitled > 0 ? $days(max(0, $entitled - $takenBefore - $thisTime)) : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <div class="doc-boxes">
            <section class="doc-box">
                <h3 class="doc-box__title">ความเห็นผู้บังคับบัญชา / ผู้อนุมัติ</h3>
                <div class="checks">
                    <span @class(['check', 'is-on' => $status === LeaveRequest::STATUS_APPROVED])><i></i>อนุมัติ</span>
                    <span @class(['check', 'is-on' => $status === LeaveRequest::STATUS_REJECTED])><i></i>ไม่อนุมัติ</span>
                </div>
                <div class="doc-lines">{{ $leaveRequest->rejection_reason ? 'เหตุผล: '.$leaveRequest->rejection_reason : '' }}</div>
                <x-doc.signature role="ผู้อนุมัติ"
                                 :name="$decided ? $approverName : null"
                                 :date="$decided && $leaveRequest->approved_at ? thai_date($leaveRequest->approved_at, 'numeric') : null" />
            </section>

            <section class="doc-box">
                <h3 class="doc-box__title">สำหรับฝ่ายบุคคล</h3>
                <div class="checks">
                    <span class="check"><i></i>ตรวจสอบสิทธิ์การลาแล้ว</span>
                    <span class="check is-on"><i></i>บันทึกในระบบแล้ว</span>
                </div>
                <div class="doc-lines"></div>
                <x-doc.signature role="เจ้าหน้าที่ฝ่ายบุคคล" />
            </section>
        </div>

        <x-doc.footer />
    </article>
@endsection
