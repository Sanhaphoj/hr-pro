<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    /**
     * Default checklist created for a new employee on first visit.
     *
     * @var array<int, array{0:string,1:string}>  [title, description]
     */
    public const TEMPLATE = [
        ['กรอกข้อมูลส่วนตัวให้ครบถ้วน', 'ตรวจสอบและอัปเดตโปรไฟล์ของคุณ'],
        ['อัปโหลดเอกสารประจำตัว', 'สำเนาบัตรประชาชน / ทะเบียนบ้าน'],
        ['อ่านคู่มือพนักงานและนโยบายบริษัท', 'ดูได้ที่เมนูคลังเอกสาร'],
        ['ตั้งค่าการลงเวลาทำงาน', 'ทดลองลงเวลาเข้า–ออกงานครั้งแรก'],
        ['ลงทะเบียนหลักสูตรปฐมนิเทศ', 'เลือกหลักสูตรที่เมนูฝึกอบรม'],
    ];

    protected $fillable = [
        'employee_id', 'title', 'description', 'is_done', 'completed_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
