<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => [
                'required', 'string', 'max:80', 'alpha_dash',
                Rule::unique('roles', 'slug')->ignore(optional($this->route('role'))->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อบทบาท',
            'name.max' => 'ชื่อบทบาทต้องไม่เกิน 80 ตัวอักษร',
            'slug.required' => 'กรุณากรอกรหัสบทบาท (slug)',
            'slug.max' => 'รหัสบทบาทต้องไม่เกิน 80 ตัวอักษร',
            'slug.alpha_dash' => 'รหัสบทบาทต้องเป็นตัวอักษร ตัวเลข ขีดกลาง หรือขีดล่างเท่านั้น',
            'slug.unique' => 'รหัสบทบาทนี้ถูกใช้งานแล้ว',
            'permissions.*.exists' => 'สิทธิ์ที่เลือกไม่ถูกต้อง',
        ];
    }
}
