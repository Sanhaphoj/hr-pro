<?php

namespace App\Http\Requests;

use App\Support\PasswordPolicy;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ForcePasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * No current-password field: the holder has just signed in with it. The
     * new one only has to differ from the password they were handed.
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'confirmed',
                PasswordPolicy::rule(),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Hash::check((string) $value, $this->user()->password)) {
                        $fail('รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านชั่วคราว');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return PasswordPolicy::messages() + [
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ];
    }
}
