<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = optional($this->route('department'))->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable', 'integer', 'exists:departments,id',
                Rule::notIn([$departmentId]),
            ],
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
            'parent_id.not_in' => 'ไม่สามารถเลือกแผนกตัวเองเป็นแผนกแม่ได้',
            'manager_id.exists' => 'ไม่พบพนักงานที่เลือกเป็นหัวหน้าแผนก',
        ];
    }
}
