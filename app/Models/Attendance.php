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
        'clock_in_location_id',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy',
        'clock_in_distance',
        'clock_in_off_site_reason',
        'clock_in_photo',
        'clock_out',
        'clock_out_location_id',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_accuracy',
        'clock_out_distance',
        'clock_out_off_site_reason',
        'clock_out_photo',
        'worked_minutes',
        'status',
        'late_reason',
        'left_early',
        'early_leave_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'clock_in_latitude' => 'float',
            'clock_in_longitude' => 'float',
            'clock_in_accuracy' => 'integer',
            'clock_in_distance' => 'integer',
            'clock_out_latitude' => 'float',
            'clock_out_longitude' => 'float',
            'clock_out_accuracy' => 'integer',
            'clock_out_distance' => 'integer',
            'worked_minutes' => 'integer',
            'left_early' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function clockInLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'clock_in_location_id');
    }

    public function clockOutLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'clock_out_location_id');
    }

    public function getWorkedHoursAttribute(): float
    {
        return round($this->worked_minutes / 60, 2);
    }

    /**
     * Punched with a position that matched no approved zone. A reason is
     * optional, so this is read from the stored position rather than from
     * whether one was given. The distance is only recorded when there was a
     * zone to measure against, so a punch from before any zone existed does
     * not count as off-site.
     */
    public function getClockInOffSiteAttribute(): bool
    {
        return $this->clock_in_latitude !== null
            && $this->clock_in_location_id === null
            && $this->clock_in_distance !== null;
    }

    public function getClockOutOffSiteAttribute(): bool
    {
        return $this->clock_out_latitude !== null
            && $this->clock_out_location_id === null
            && $this->clock_out_distance !== null;
    }
}
