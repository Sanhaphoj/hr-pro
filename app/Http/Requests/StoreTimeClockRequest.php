<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeClockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:in,out'],
            // Coordinates arrive together or not at all; the service decides
            // whether a missing reading is acceptable for this installation.
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            // Free-text explanation for a late arrival or an early exit.
            // Always optional; the service drops it when it does not apply.
            'reason' => ['nullable', 'string', 'max:250'],
            // Why the punch is being taken off-site. Optional, and dropped by
            // the service unless the punch really lands outside every zone.
            'off_site_reason' => ['nullable', 'string', 'max:250'],
            // Front-camera photo as a JPEG data URL. Whether one is required
            // depends on the role, which the controller decides.
            'photo' => ['nullable', 'string', 'max:2100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'ไม่พบประเภทการลงเวลา',
            'action.in' => 'ประเภทการลงเวลาไม่ถูกต้อง',
            'latitude.required_with' => 'ตำแหน่งที่ส่งมาไม่ครบถ้วน',
            'latitude.numeric' => 'ค่าละติจูดไม่ถูกต้อง',
            'latitude.between' => 'ค่าละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'longitude.required_with' => 'ตำแหน่งที่ส่งมาไม่ครบถ้วน',
            'longitude.numeric' => 'ค่าลองจิจูดไม่ถูกต้อง',
            'longitude.between' => 'ค่าลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
            'accuracy.numeric' => 'ค่าความคลาดเคลื่อนของตำแหน่งไม่ถูกต้อง',
            'off_site_reason.max' => 'เหตุผลที่ลงเวลานอกสถานที่ต้องไม่เกิน 250 ตัวอักษร',
            'photo.max' => 'ไฟล์ภาพใหญ่เกินไป กรุณาถ่ายใหม่',
            'reason.max' => 'เหตุผลต้องไม่เกิน 250 ตัวอักษร',
        ];
    }

    /**
     * The GPS reading in the shape AttendanceService expects, or null when the
     * browser could not supply one.
     *
     * @return array{latitude: float, longitude: float, accuracy: int|null}|null
     */
    public function coordinates(): ?array
    {
        if ($this->filled('latitude') === false || $this->filled('longitude') === false) {
            return null;
        }

        return [
            'latitude' => (float) $this->input('latitude'),
            'longitude' => (float) $this->input('longitude'),
            'accuracy' => $this->filled('accuracy') ? (int) round((float) $this->input('accuracy')) : null,
        ];
    }
}
