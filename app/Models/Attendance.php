<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    public const STATUSES = ['present', 'late', 'absent', 'half_day', 'on_leave'];

    protected $fillable = [
        'employee_id',
        'work_date',
        'clock_in',
        'clock_out',
        'worked_minutes',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'worked_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getWorkedHoursAttribute(): float
    {
        return round($this->worked_minutes / 60, 2);
    }
}
