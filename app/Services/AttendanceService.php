<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function todayFor(Employee $employee): ?Attendance
    {
        return Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', Carbon::today())
            ->first();
    }

    /**
     * Record a clock-in for today, flagging lateness against the work schedule.
     *
     * @throws ValidationException
     */
    public function clockIn(Employee $employee): Attendance
    {
        $today = Carbon::today();

        // Use the whereDate-based lookup so an existing record is matched
        // regardless of how the driver stores the DATE column (MySQL keeps
        // 'Y-m-d', SQLite keeps the full datetime string).
        $attendance = $this->todayFor($employee) ?? new Attendance([
            'employee_id' => $employee->id,
            'work_date' => $today->toDateString(),
        ]);

        if ($attendance->clock_in) {
            throw ValidationException::withMessages(['attendance' => 'คุณได้ลงเวลาเข้างานแล้วในวันนี้']);
        }

        $now = Carbon::now();
        $start = Carbon::parse($today->toDateString().' '.config('hrpro.work.start', '09:00'));
        $grace = (int) config('hrpro.work.late_grace_minutes', 15);

        $attendance->clock_in = $now;
        $attendance->status = $now->greaterThan($start->copy()->addMinutes($grace))
            ? 'late'
            : 'present';
        $attendance->worked_minutes = 0;
        $attendance->save();

        return $attendance;
    }

    /**
     * Record a clock-out and compute the minutes worked.
     *
     * @throws ValidationException
     */
    public function clockOut(Employee $employee): Attendance
    {
        $attendance = $this->todayFor($employee);

        if (! $attendance || ! $attendance->clock_in) {
            throw ValidationException::withMessages(['attendance' => 'คุณยังไม่ได้ลงเวลาเข้างานในวันนี้']);
        }

        if ($attendance->clock_out) {
            throw ValidationException::withMessages(['attendance' => 'คุณได้ลงเวลาออกงานแล้วในวันนี้']);
        }

        $now = Carbon::now();
        $attendance->clock_out = $now;
        $attendance->worked_minutes = (int) round(abs($attendance->clock_in->diffInMinutes($now)));
        $attendance->save();

        return $attendance;
    }
}
