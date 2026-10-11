<?php

namespace App\Http\Requests;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:160',
                Rule::unique('users', 'email')->ignore(optional($this->route('user'))->id),
            ],
            'password' => ['nullable', 'string', 'confirmed', PasswordPolicy::rule()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return PasswordPolicy::messages() + [
            'name.required' => 'กรุณากรอกชื่อ',
            'name.max' => 'ชื่อต้องไม่เกิน 120 ตัวอักษร',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
            'roles.*.exists' => 'บทบาทที่เลือกไม่ถูกต้อง',
        ];
    }
}
