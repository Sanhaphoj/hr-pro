<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('leave_types', 'code')->ignore(optional($this->route('leaveType'))->id),
            ],
            'days_per_year' => ['required', 'integer', 'min:0', 'max:366'],
            'color' => ['required', 'in:blue,green,amber,red,gray'],
            'requires_approval' => ['boolean'],
            'is_paid' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อประเภทการลา',
            'name.max' => 'ชื่อประเภทการลาต้องไม่เกิน 100 ตัวอักษร',
            'code.required' => 'กรุณากรอกรหัสประเภทการลา',
            'code.max' => 'รหัสประเภทการลาต้องไม่เกิน 20 ตัวอักษร',
            'code.unique' => 'รหัสประเภทการลานี้ถูกใช้งานแล้ว',
            'days_per_year.required' => 'กรุณากรอกจำนวนวันลาต่อปี',
            'days_per_year.integer' => 'จำนวนวันลาต่อปีต้องเป็นตัวเลขจำนวนเต็ม',
            'days_per_year.min' => 'จำนวนวันลาต่อปีต้องไม่น้อยกว่า 0',
            'days_per_year.max' => 'จำนวนวันลาต่อปีต้องไม่เกิน 366',
            'color.required' => 'กรุณาเลือกสี',
            'color.in' => 'สีที่เลือกไม่ถูกต้อง',
            'description.string' => 'รายละเอียดไม่ถูกต้อง',
        ];
    }
}
