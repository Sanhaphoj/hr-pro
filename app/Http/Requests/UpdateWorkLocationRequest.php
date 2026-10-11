<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('work_locations', 'code')->ignore(optional($this->route('workLocation'))->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:20', 'max:20000'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อบริเวณลงเวลา',
            'name.max' => 'ชื่อบริเวณต้องไม่เกิน 120 ตัวอักษร',
            'code.required' => 'กรุณากรอกรหัสบริเวณ',
            'code.max' => 'รหัสบริเวณต้องไม่เกิน 30 ตัวอักษร',
            'code.unique' => 'รหัสบริเวณนี้ถูกใช้งานแล้ว',
            'address.max' => 'ที่อยู่ต้องไม่เกิน 255 ตัวอักษร',
            'latitude.required' => 'กรุณาระบุละติจูด',
            'latitude.numeric' => 'ละติจูดต้องเป็นตัวเลข',
            'latitude.between' => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'longitude.required' => 'กรุณาระบุลองจิจูด',
            'longitude.numeric' => 'ลองจิจูดต้องเป็นตัวเลข',
            'longitude.between' => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
            'radius_meters.required' => 'กรุณาระบุรัศมีของบริเวณ',
            'radius_meters.integer' => 'รัศมีต้องเป็นตัวเลขจำนวนเต็ม',
            'radius_meters.min' => 'รัศมีต้องไม่น้อยกว่า 20 เมตร (เล็กกว่านี้ความคลาดเคลื่อนของ GPS จะทำให้ลงเวลาไม่ผ่าน)',
            'radius_meters.max' => 'รัศมีต้องไม่เกิน 20,000 เมตร',
            'notes.max' => 'หมายเหตุต้องไม่เกิน 255 ตัวอักษร',
        ];
    }
}
