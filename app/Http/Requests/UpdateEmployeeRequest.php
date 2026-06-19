<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:160',
                Rule::unique('employees', 'email')->ignore(optional($this->route('employee'))->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(Employee::GENDERS)],
            'address' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_id' => [
                'nullable', 'exists:employees,id',
                Rule::notIn([optional($this->route('employee'))->id]),
            ],
            'employment_type' => ['required', Rule::in(Employee::EMPLOYMENT_TYPES)],
            'status' => ['required', Rule::in(Employee::STATUSES)],
            'hire_date' => ['required', 'date'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.max' => 'ชื่อต้องไม่เกิน 100 ตัวอักษร',
            'last_name.required' => 'กรุณากรอกนามสกุล',
            'last_name.max' => 'นามสกุลต้องไม่เกิน 100 ตัวอักษร',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'date_of_birth.date' => 'รูปแบบวันเกิดไม่ถูกต้อง',
            'gender.in' => 'เพศที่เลือกไม่ถูกต้อง',
            'address.max' => 'ที่อยู่ต้องไม่เกิน 500 ตัวอักษร',
            'department_id.exists' => 'ไม่พบแผนกที่เลือก',
            'position_id.exists' => 'ไม่พบตำแหน่งที่เลือก',
            'manager_id.exists' => 'ไม่พบหัวหน้างานที่เลือก',
            'manager_id.not_in' => 'พนักงานไม่สามารถเป็นหัวหน้างานของตนเองได้',
            'employment_type.required' => 'กรุณาเลือกประเภทการจ้างงาน',
            'employment_type.in' => 'ประเภทการจ้างงานไม่ถูกต้อง',
            'status.required' => 'กรุณาเลือกสถานะพนักงาน',
            'status.in' => 'สถานะพนักงานไม่ถูกต้อง',
            'hire_date.required' => 'กรุณาระบุวันเริ่มงาน',
            'hire_date.date' => 'รูปแบบวันเริ่มงานไม่ถูกต้อง',
            'base_salary.numeric' => 'เงินเดือนต้องเป็นตัวเลข',
            'base_salary.min' => 'เงินเดือนต้องไม่ติดลบ',
        ];
    }
}
