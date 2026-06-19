<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'กรุณาระบุเหตุผลในการปฏิเสธ',
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ];
    }
}
