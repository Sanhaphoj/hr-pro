<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(['general', 'policy', 'event', 'urgent'])],
            'body' => ['required', 'string'],
            'is_published' => ['boolean'],
            'pinned' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'กรุณากรอกหัวข้อประกาศ',
            'title.max' => 'หัวข้อประกาศต้องไม่เกิน 160 ตัวอักษร',
            'category.required' => 'กรุณาเลือกหมวดหมู่',
            'category.in' => 'หมวดหมู่ที่เลือกไม่ถูกต้อง',
            'body.required' => 'กรุณากรอกเนื้อหาประกาศ',
            'published_at.date' => 'รูปแบบวันที่เผยแพร่ไม่ถูกต้อง',
        ];
    }
}
