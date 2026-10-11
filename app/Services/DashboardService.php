<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Headline KPI counters shown on the dashboard tiles.
     */
    public function metrics(): array
    {
        $today = Carbon::today();

        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::active()->count(),
            'on_leave_today' => LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->distinct('employee_id')
                ->count('employee_id'),
            'pending_requests' => LeaveRequest::pending()->count(),
            'present_today' => Attendance::whereDate('work_date', $today)
                ->whereNotNull('clock_in')
                ->count(),
            'departments' => Department::count(),
        ];
    }

    /**
     * Headcount per department for the bar visualisation.
     */
    public function headcountByDepartment(int $limit = 8): Collection
    {
        return Department::query()
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->limit($limit)
            ->get(['id', 'name']);
    }

    public function recentLeaveRequests(int $limit = 6): Collection
    {
        return LeaveRequest::with(['employee:id,first_name,last_name', 'leaveType:id,name,color'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function latestAnnouncements(int $limit = 4): Collection
    {
        return Announcement::published()
            ->visibleOrder()
            ->with('author:id,name')
            ->limit($limit)
            ->get();
    }

    public function newHires(int $limit = 5): Collection
    {
        return Employee::with(['position:id,title', 'department:id,name'])
            ->orderByDesc('hire_date')
            ->limit($limit)
            ->get();
    }

    /**
     * On-time and late clock-ins for each of the last $days working days,
     * oldest first, for the stacked attendance chart.
     *
     * @return array<int, array{date: Carbon, on_time: int, late: int}>
     */
    public function attendanceTrend(int $days = 14): array
    {
        $workingDays = (array) config('hrpro.work.working_days', [1, 2, 3, 4, 5]);

        $dates = [];
        for ($day = Carbon::today(); count($dates) < $days; $day = $day->copy()->subDay()) {
            if (in_array($day->dayOfWeekIso, $workingDays, true)) {
                $dates[] = $day;
            }
        }
        $dates = array_reverse($dates);

        $counts = Attendance::query()
            ->whereNotNull('clock_in')
            ->whereDate('work_date', '>=', $dates[0])
            ->whereDate('work_date', '<=', end($dates))
            ->get(['work_date', 'status'])
            ->groupBy(fn (Attendance $row) => $row->work_date->toDateString());

        return array_map(function (Carbon $date) use ($counts) {
            $rows = $counts->get($date->toDateString(), collect());
            $late = $rows->where('status', 'late')->count();

            return ['date' => $date, 'on_time' => $rows->count() - $late, 'late' => $late];
        }, $dates);
    }

    /**
     * Approved leave days this year per leave type, largest first.
     *
     * @return Collection<int, array{name: string, days: float}>
     */
    public function leaveDaysByType(?int $year = null): Collection
    {
        $year ??= now()->year;

        return LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->with('leaveType:id,name')
            ->get(['leave_type_id', 'total_days'])
            ->groupBy('leave_type_id')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->leaveType?->name ?? '—',
                'days' => (float) $rows->sum('total_days'),
            ])
            ->sortByDesc('days')
            ->values();
    }

    /**
     * Birthdays and work anniversaries that fall in the current month,
     * today first, then the days still to come, then the ones gone by.
     * Only the day is shown, never the year of birth.
     *
     * @return Collection<int, array{employee: Employee, kind: string, day: int, years: int|null, today: bool}>
     */
    public function celebrations(int $limit = 6): Collection
    {
        $today = Carbon::today();

        return Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->get(['id', 'first_name', 'last_name', 'email', 'date_of_birth', 'hire_date'])
            ->flatMap(function (Employee $employee) use ($today) {
                $events = [];
                if ($employee->date_of_birth?->month === $today->month) {
                    $events[] = ['employee' => $employee, 'kind' => 'birthday', 'day' => $employee->date_of_birth->day, 'years' => null];
                }
                if ($employee->hire_date?->month === $today->month && $employee->hire_date->year < $today->year) {
                    $events[] = ['employee' => $employee, 'kind' => 'anniversary', 'day' => $employee->hire_date->day,
                        'years' => $today->year - $employee->hire_date->year];
                }

                return $events;
            })
            ->map(fn (array $event) => $event + ['today' => $event['day'] === $today->day])
            ->sortBy(fn (array $event) => ($event['day'] < $today->day ? 100 : 0) + $event['day'])
            ->take($limit)
            ->values();
    }

    /**
     * The signed-in employee's own month at a glance.
     *
     * @return array{today: Attendance|null, balances: Collection, worked_hours: float, late_days: int, pending: int}
     */
    public function personal(Employee $employee): array
    {
        $today = Carbon::today();
        $month = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $today->copy()->startOfMonth())
            ->whereDate('work_date', '<=', $today)
            ->get(['status', 'worked_minutes']);

        return [
            'today' => Attendance::where('employee_id', $employee->id)->whereDate('work_date', $today)->first(),
            'balances' => LeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('year', $today->year)
                ->with('leaveType:id,name')
                ->get()
                ->filter(fn (LeaveBalance $balance) => (float) $balance->entitled_days > 0)
                ->values(),
            'worked_hours' => round($month->sum('worked_minutes') / 60, 1),
            'late_days' => $month->where('status', 'late')->count(),
            'pending' => LeaveRequest::pending()->where('employee_id', $employee->id)->count(),
        ];
    }

    /**
     * Where each member of the employee's team is today: the people who
     * report to them directly, plus everyone in a department they head.
     * Exceptions (late, not in yet) sort first — they are what a lead acts on.
     *
     * @return Collection<int, array{employee: Employee, state: string, time: Carbon|null}>
     */
    public function teamToday(Employee $lead): Collection
    {
        $today = Carbon::today();
        $headed = Department::where('manager_id', $lead->id)->pluck('id');

        $team = Employee::query()
            ->whereKeyNot($lead->id)
            ->whereIn('status', ['active', 'probation', 'on_leave'])
            ->where(fn ($q) => $q->where('manager_id', $lead->id)->orWhereIn('department_id', $headed))
            ->with('position:id,title')
            ->orderBy('first_name')
            ->get();

        if ($team->isEmpty()) {
            return collect();
        }

        $punches = Attendance::whereIn('employee_id', $team->pluck('id'))
            ->whereDate('work_date', $today)
            ->get()
            ->keyBy('employee_id');
        $onLeave = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereIn('employee_id', $team->pluck('id'))
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->flip();

        $order = ['late' => 0, 'absent' => 1, 'leave' => 2, 'in' => 3, 'out' => 4];

        return $team
            ->map(function (Employee $employee) use ($punches, $onLeave) {
                $punch = $punches->get($employee->id);
                $state = match (true) {
                    $onLeave->has($employee->id) => 'leave',
                    ! $punch?->clock_in => 'absent',
                    (bool) $punch->clock_out => 'out',
                    $punch->status === 'late' => 'late',
                    default => 'in',
                };

                return ['employee' => $employee, 'state' => $state, 'time' => $punch?->clock_in];
            })
            ->sortBy(fn (array $row) => $order[$row['state']])
            ->values();
    }
}
