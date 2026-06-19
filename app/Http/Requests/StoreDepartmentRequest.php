<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อแผนก',
            'name.max' => 'ชื่อแผนกต้องไม่เกิน 120 ตัวอักษร',
            'code.required' => 'กรุณากรอกรหัสแผนก',
            'code.max' => 'รหัสแผนกต้องไม่เกิน 30 ตัวอักษร',
            'code.unique' => 'รหัสแผนกนี้ถูกใช้งานแล้ว',
            'parent_id.exists' => 'ไม่พบแผนกแม่ที่เลือก',
            'manager_id.exists' => 'ไม่พบพนักงานที่เลือกเป็นหัวหน้าแผนก',
        ];
    }
}
