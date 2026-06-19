@props(['type' => 'employee', 'value'])
@php
    $map = [
        'employee' => [
            'active' => ['green', 'ทำงานปกติ'],
            'probation' => ['amber', 'ทดลองงาน'],
            'on_leave' => ['blue', 'กำลังลา'],
            'suspended' => ['amber', 'พักงาน'],
            'terminated' => ['red', 'พ้นสภาพ'],
        ],
        'employment' => [
            'full_time' => ['blue', 'พนักงานประจำ'],
            'part_time' => ['gray', 'พาร์ทไทม์'],
            'contract' => ['amber', 'สัญญาจ้าง'],
            'intern' => ['gray', 'นักศึกษาฝึกงาน'],
        ],
        'leave' => [
            'pending' => ['amber', 'รออนุมัติ'],
            'approved' => ['green', 'อนุมัติแล้ว'],
            'rejected' => ['red', 'ไม่อนุมัติ'],
            'cancelled' => ['gray', 'ยกเลิก'],
        ],
        'attendance' => [
            'present' => ['green', 'มาทำงาน'],
            'late' => ['amber', 'มาสาย'],
            'absent' => ['red', 'ขาดงาน'],
            'half_day' => ['blue', 'ครึ่งวัน'],
            'on_leave' => ['gray', 'ลางาน'],
        ],
        'announcement' => [
            'general' => ['gray', 'ทั่วไป'],
            'policy' => ['blue', 'นโยบาย'],
            'event' => ['green', 'กิจกรรม'],
            'urgent' => ['red', 'ด่วน'],
        ],
        'active' => [
            '1' => ['green', 'เปิดใช้งาน'],
            '0' => ['gray', 'ปิดใช้งาน'],
        ],
    ];
    $key = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    [$color, $label] = $map[$type][$key] ?? ['gray', $key];
@endphp
<span class="badge badge--{{ $color }} badge--dot">{{ $label }}</span>
