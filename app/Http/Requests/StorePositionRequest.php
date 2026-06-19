<?php

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:positions,code'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'level' => ['required', Rule::in(Position::LEVELS)],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'กรุณากรอกชื่อตำแหน่ง',
            'title.max' => 'ชื่อตำแหน่งต้องไม่เกิน 120 ตัวอักษร',
            'code.required' => 'กรุณากรอกรหัสตำแหน่ง',
            'code.max' => 'รหัสตำแหน่งต้องไม่เกิน 30 ตัวอักษร',
            'code.unique' => 'รหัสตำแหน่งนี้ถูกใช้งานแล้ว',
            'department_id.exists' => 'ไม่พบแผนกที่เลือก',
            'level.required' => 'กรุณาเลือกระดับตำแหน่ง',
            'level.in' => 'ระดับตำแหน่งไม่ถูกต้อง',
        ];
    }
}
