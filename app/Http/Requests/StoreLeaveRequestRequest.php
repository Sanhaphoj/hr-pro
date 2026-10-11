<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $startDate = ['required', 'date'];

        // Settings → Leave: how far back a request may start.
        if ($this->backdateDays() !== null) {
            $startDate[] = 'after_or_equal:'.now()->subDays($this->backdateDays())->toDateString();
        }

        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => $startDate,
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => [config('hrpro.leave.reason_required', true) ? 'required' : 'nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.($this->attachmentMaxMb() * 1024)],
        ];
    }

    public function messages(): array
    {
        $backdate = $this->backdateDays();

        return [
            'leave_type_id.required' => 'กรุณาเลือกประเภทการลา',
            'leave_type_id.exists' => 'ประเภทการลาที่เลือกไม่ถูกต้อง',
            'start_date.required' => 'กรุณาเลือกวันที่เริ่มลา',
            'start_date.date' => 'รูปแบบวันที่เริ่มลาไม่ถูกต้อง',
            'start_date.after_or_equal' => $backdate === 0 ? 'ไม่สามารถยื่นลาย้อนหลังได้' : "ยื่นลาย้อนหลังได้ไม่เกิน {$backdate} วัน",
            'end_date.required' => 'กรุณาเลือกวันที่สิ้นสุดการลา',
            'end_date.date' => 'รูปแบบวันที่สิ้นสุดการลาไม่ถูกต้อง',
            'end_date.after_or_equal' => 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มลา',
            'reason.required' => 'กรุณากรอกเหตุผลการลา',
            'reason.max' => 'เหตุผลการลาต้องไม่เกิน 500 ตัวอักษร',
            'attachment.file' => 'ไฟล์แนบไม่ถูกต้อง',
            'attachment.mimes' => 'ไฟล์แนบต้องเป็นไฟล์ jpg, jpeg, png หรือ pdf เท่านั้น',
            'attachment.max' => 'ไฟล์แนบต้องมีขนาดไม่เกิน '.$this->attachmentMaxMb().' MB',
        ];
    }

    private function backdateDays(): ?int
    {
        $days = config('hrpro.leave.backdate_days');

        return $days === null || $days === '' ? null : (int) $days;
    }

    private function attachmentMaxMb(): int
    {
        return max(1, (int) config('hrpro.leave.attachment_max_mb', 4));
    }
}
