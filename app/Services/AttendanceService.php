<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private readonly GeofenceService $geofence) {}

    public function todayFor(Employee $employee): ?Attendance
    {
        return Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', Carbon::today())
            ->first();
    }

    /**
     * Head-count of where the organisation stands on a given day, for the
     * supervisor strip at the top of the time clock screen.
     *
     * @return array{headcount: int, on_time: int, late: int, outside: int, on_leave: int, not_yet: int}
     */
    public function dailySummary(?Carbon $date = null): array
    {
        $day = ($date ?? Carbon::today())->toDateString();

        $headcount = Employee::active()->count();

        $onLeave = LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->distinct()
            ->count('employee_id');

        $punched = Attendance::whereDate('work_date', $day)->whereNotNull('clock_in');

        return [
            'headcount' => $headcount,
            'on_time' => (clone $punched)->where('status', 'present')->count(),
            'late' => (clone $punched)->where('status', 'late')->count(),
            // Punched outside every approved zone. A recorded distance means
            // there was a zone to measure against, which leaves out punches
            // taken before any zone was set up.
            'outside' => (clone $punched)
                ->whereNotNull('clock_in_latitude')
                ->whereNull('clock_in_location_id')
                ->whereNotNull('clock_in_distance')
                ->count(),
            'on_leave' => $onLeave,
            'not_yet' => max($headcount - (clone $punched)->count() - $onLeave, 0),
        ];
    }

    /**
     * The moment after which arriving counts as late — the configured start
     * plus its grace period.
     */
    public function lateThreshold(?Carbon $date = null): Carbon
    {
        $day = ($date ?? Carbon::today())->toDateString();

        return Carbon::parse($day.' '.config('hrpro.work.start', '09:00'))
            ->addMinutes((int) config('hrpro.work.late_grace_minutes', 15));
    }

    /**
     * The moment before which leaving counts as early — the configured end
     * minus its grace period.
     */
    public function earlyThreshold(?Carbon $date = null): Carbon
    {
        $day = ($date ?? Carbon::today())->toDateString();

        return Carbon::parse($day.' '.config('hrpro.work.end', '18:00'))
            ->subMinutes((int) config('hrpro.work.early_grace_minutes', 15));
    }

    /**
     * Record a clock-in for today, flagging lateness against the work schedule.
     *
     * @param  array{latitude: float, longitude: float, accuracy?: int|null}|null  $coords
     *                                                                             GPS reading captured by the browser, when the employee shared one.
     * @param  string|null  $reason  Optional explanation, kept only when the punch is actually late.
     *
     * @throws ValidationException
     */
    public function clockIn(Employee $employee, ?array $coords = null, ?string $reason = null, ?string $offSiteReason = null): Attendance
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

        // Resolve the position before writing anything, so a refused punch
        // (no fix, or one too imprecise to judge) leaves no half-row.
        $placement = $this->resolvePlacement($coords);
        $offSite = $this->offSiteReason($placement, $offSiteReason);

        $now = Carbon::now();
        $isLate = $now->greaterThan($this->lateThreshold($today));

        $attendance->clock_in = $now;
        $attendance->status = $isLate ? 'late' : 'present';
        // The reason belongs to a late arrival; discard it on a punctual punch
        // so the browser cannot attach an explanation to a normal day.
        $lateReason = $isLate ? $this->trimReason($reason) : null;
        if ($isLate && $lateReason === null && config('hrpro.attendance.late_reason_required', false)) {
            throw ValidationException::withMessages(['attendance' => 'กรุณาระบุเหตุผลที่มาสายก่อนลงเวลา']);
        }
        $attendance->late_reason = $lateReason;
        $attendance->worked_minutes = 0;
        $attendance->clock_in_off_site_reason = $offSite;
        $attendance->fill($this->placementColumns('clock_in', $placement));
        $attendance->save();

        return $attendance;
    }

    /**
     * Record a clock-out and compute the minutes worked.
     *
     * @param  array{latitude: float, longitude: float, accuracy?: int|null}|null  $coords
     * @param  string|null  $reason  Optional explanation, kept only when the employee leaves early.
     *
     * @throws ValidationException
     */
    public function clockOut(Employee $employee, ?array $coords = null, ?string $reason = null, ?string $offSiteReason = null): Attendance
    {
        $attendance = $this->todayFor($employee);

        if (! $attendance || ! $attendance->clock_in) {
            throw ValidationException::withMessages(['attendance' => 'คุณยังไม่ได้ลงเวลาเข้างานในวันนี้']);
        }

        if ($attendance->clock_out) {
            throw ValidationException::withMessages(['attendance' => 'คุณได้ลงเวลาออกงานแล้วในวันนี้']);
        }

        $placement = $this->resolvePlacement($coords);
        $offSite = $this->offSiteReason($placement, $offSiteReason);

        $now = Carbon::now();
        $leftEarly = $now->lessThan($this->earlyThreshold($attendance->work_date));

        $attendance->clock_out = $now;
        $attendance->left_early = $leftEarly;
        $earlyReason = $leftEarly ? $this->trimReason($reason) : null;
        if ($leftEarly && $earlyReason === null && config('hrpro.attendance.early_reason_required', false)) {
            throw ValidationException::withMessages(['attendance' => 'กรุณาระบุเหตุผลที่ออกก่อนเวลาก่อนลงเวลา']);
        }
        $attendance->early_leave_reason = $earlyReason;
        $attendance->worked_minutes = (int) round(abs($attendance->clock_in->diffInMinutes($now)));
        $attendance->clock_out_off_site_reason = $offSite;
        $attendance->fill($this->placementColumns('clock_out', $placement));
        $attendance->save();

        return $attendance;
    }

    /**
     * Normalise a free-text reason: blank and whitespace-only entries are the
     * same as declining to give one.
     */
    private function trimReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : Str::limit($reason, 250, '');
    }

    /**
     * Validate a GPS reading against the approved zones and return what should
     * be stored alongside the punch.
     *
     * @param  array{latitude: float, longitude: float, accuracy?: int|null}|null  $coords
     * `off_site` is true when an enforced punch matched no zone. It is not
     * refused for that; the caller records it as off-site.
     *
     * @return array{location_id: int|null, latitude: float|null, longitude: float|null, accuracy: int|null, distance: int|null, off_site: bool}
     *
     * @throws ValidationException
     */
    private function resolvePlacement(?array $coords): array
    {
        $empty = ['location_id' => null, 'latitude' => null, 'longitude' => null, 'accuracy' => null, 'distance' => null, 'off_site' => false];

        // With no zone defined yet, enforcing the fence would lock everyone
        // out — fall back to recording whatever position was offered.
        $enforced = (bool) config('hrpro.attendance.require_location', true) && $this->geofence->hasZones();

        if ($coords === null) {
            if ($enforced) {
                throw ValidationException::withMessages([
                    'attendance' => 'ต้องระบุตำแหน่งของคุณก่อนลงเวลา กรุณาอนุญาตให้เบราว์เซอร์เข้าถึงตำแหน่ง',
                ]);
            }

            return $empty;
        }

        $latitude = (float) $coords['latitude'];
        $longitude = (float) $coords['longitude'];
        $accuracy = isset($coords['accuracy']) ? (int) round($coords['accuracy']) : null;

        $maxAccuracy = (int) config('hrpro.attendance.max_accuracy_meters', 200);

        if ($enforced && $accuracy !== null && $accuracy > $maxAccuracy) {
            throw ValidationException::withMessages([
                'attendance' => "สัญญาณตำแหน่งไม่แม่นยำพอ (คลาดเคลื่อน ±{$accuracy} ม. เกินเกณฑ์ {$maxAccuracy} ม.) กรุณาลองใหม่ในที่โล่ง",
            ]);
        }

        $match = $this->geofence->match($latitude, $longitude);

        $nearest = $match ?? $this->geofence->nearest($latitude, $longitude);

        return [
            'location_id' => $match ? $match['location']->id : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'distance' => $nearest ? (int) round($nearest['distance']) : null,
            'off_site' => $enforced && $match === null,
        ];
    }

    /**
     * An enforced punch outside every approved zone is recorded as off-site,
     * with the employee's explanation. Settings decide whether such a punch
     * is allowed at all and whether the explanation is compulsory. Inside a
     * zone, or with enforcement off, a reason sent along is dropped.
     *
     * @param  array{off_site: bool}  $placement
     *
     * @throws ValidationException
     */
    private function offSiteReason(array $placement, ?string $reason): ?string
    {
        if (! $placement['off_site']) {
            return null;
        }

        if (! config('hrpro.attendance.allow_off_site', true)) {
            throw ValidationException::withMessages([
                'attendance' => 'คุณอยู่นอกบริเวณที่อนุญาต ระบบกำหนดให้ลงเวลาได้เฉพาะภายในบริเวณที่กำหนดเท่านั้น',
            ]);
        }

        $reason = $this->trimReason($reason);

        if ($reason === null && config('hrpro.attendance.off_site_reason_required', false)) {
            throw ValidationException::withMessages(['attendance' => 'กรุณาระบุเหตุผลที่ลงเวลานอกสถานที่']);
        }

        return $reason;
    }


    /**
     * Map a resolved placement onto the clock_in_* / clock_out_* columns.
     *
     * @param  array{location_id: int|null, latitude: float|null, longitude: float|null, accuracy: int|null, distance: int|null}  $placement
     * @return array<string, mixed>
     */
    private function placementColumns(string $prefix, array $placement): array
    {
        $columns = [];
        // A flag for the caller, not a column.
        unset($placement['off_site']);

        foreach ($placement as $key => $value) {
            $columns[$prefix.'_'.$key] = $value;
        }

        return $columns;
    }
}
