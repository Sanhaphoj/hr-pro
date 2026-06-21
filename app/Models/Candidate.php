<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    public const STAGES = ['applied', 'screening', 'interview', 'offer', 'hired', 'rejected'];

    public const STAGE_LABELS = [
        'applied' => 'สมัครแล้ว',
        'screening' => 'คัดกรอง',
        'interview' => 'สัมภาษณ์',
        'offer' => 'ยื่นข้อเสนอ',
        'hired' => 'รับเข้าทำงาน',
        'rejected' => 'ไม่ผ่าน',
    ];

    protected $fillable = [
        'job_posting_id', 'name', 'email', 'phone', 'stage', 'note',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
