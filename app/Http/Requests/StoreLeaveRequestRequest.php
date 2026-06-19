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
        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'กรุณาเลือกประเภทการลา',
            'leave_type_id.exists' => 'ประเภทการลาที่เลือกไม่ถูกต้อง',
            'start_date.required' => 'กรุณาเลือกวันที่เริ่มลา',
            'start_date.date' => 'รูปแบบวันที่เริ่มลาไม่ถูกต้อง',
            'end_date.required' => 'กรุณาเลือกวันที่สิ้นสุดการลา',
            'end_date.date' => 'รูปแบบวันที่สิ้นสุดการลาไม่ถูกต้อง',
            'end_date.after_or_equal' => 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มลา',
            'reason.required' => 'กรุณากรอกเหตุผลการลา',
            'reason.max' => 'เหตุผลการลาต้องไม่เกิน 500 ตัวอักษร',
            'attachment.file' => 'ไฟล์แนบไม่ถูกต้อง',
            'attachment.mimes' => 'ไฟล์แนบต้องเป็นไฟล์ jpg, jpeg, png หรือ pdf เท่านั้น',
            'attachment.max' => 'ไฟล์แนบต้องมีขนาดไม่เกิน 4 MB',
        ];
    }
}
