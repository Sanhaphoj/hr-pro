<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Attendance history. Punching in and out lives on its own screen —
 * see TimeClockController — because it needs a GPS fix first.
 */
class AttendanceController extends Controller
{
    public function index(AttendanceService $attendanceService): View
    {
        $user = auth()->user();
        $employee = $user->employee;
        $canViewAll = $user->hasPermission('attendance.viewAll');

        // Today's attendance for the summary card.
        $today = $employee ? $attendanceService->todayFor($employee) : null;

        // History list — all employees for managers, otherwise the user's own records.
        $records = null;

        if ($canViewAll) {
            $query = Attendance::query()->with(['employee', 'clockInLocation', 'clockOutLocation']);

            if ($date = request('date')) {
                $query->whereDate('work_date', $date);
            }

            $records = $query
                ->orderByDesc('work_date')
                ->orderByDesc('clock_in')
                ->paginate(config('hrpro.per_page'))
                ->withQueryString();
        } elseif ($employee) {
            $records = Attendance::with(['clockInLocation', 'clockOutLocation'])
                ->where('employee_id', $employee->id)
                ->where('work_date', '>=', now()->subDays(30)->toDateString())
                ->orderByDesc('work_date')
                ->orderByDesc('clock_in')
                ->paginate(config('hrpro.per_page'))
                ->withQueryString();
        }

        return view('attendance.index', [
            'employee' => $employee,
            'today' => $today,
            'records' => $records,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Printable attendance. Anyone with a profile gets their own monthly
     * timesheet. People who may see everyone's attendance can pick another
     * employee, or ask for one day's register across the company with ?date=.
     */
    public function print(Request $request): View
    {
        $user = $request->user();
        $canViewAll = $user->hasPermission('attendance.viewAll');

        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        // Without a profile of their own, a viewer of everyone starts on today's register.
        if ($canViewAll && (! empty($data['date']) || (empty($data['employee_id']) && ! $user->employee))) {
            return $this->dailyRegister(Carbon::parse($data['date'] ?? now()->toDateString()));
        }

        $employee = $user->employee;

        if (! empty($data['employee_id']) && (int) $data['employee_id'] !== $employee?->id) {
            abort_unless($canViewAll, 403);
            $employee = Employee::findOrFail($data['employee_id']);
        }

        abort_if($employee === null, 403, 'บัญชีนี้ยังไม่ได้เชื่อมโยงกับข้อมูลพนักงาน');

        // Built from the first of the month: a bare Y-m would take today's
        // day number and spill into the next month on the 31st.
        $month = Carbon::createFromFormat('Y-m-d', ($data['month'] ?? now()->format('Y-m')).'-01')->startOfDay();

        return $this->timesheet($employee, $month, $canViewAll);
    }

    private function timesheet(Employee $employee, Carbon $month, bool $canViewAll): View
    {
        $employee->loadMissing(['department', 'position', 'manager']);

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $workingDays = config('hrpro.work.working_days', [1, 2, 3, 4, 5]);
        $today = now()->startOfDay();

        $records = Attendance::with('clockInLocation')
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (Attendance $record) => $record->work_date->toDateString());

        $leaves = $this->approvedLeaveBetween($start, $end)
            ->where('employee_id', $employee->id)
            ->get();

        $days = [];
        $summary = ['working' => 0, 'present' => 0, 'late' => 0, 'early' => 0, 'leave' => 0, 'missing' => 0, 'off_site' => 0, 'minutes' => 0];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $record = $records->get($day->toDateString());
            $working = in_array($day->dayOfWeekIso, $workingDays, true);
            $leave = $leaves->first(fn (LeaveRequest $l) => $day->betweenIncluded($l->start_date, $l->end_date));
            $missing = $working && ! $record && ! $leave && $day->lt($today);

            $summary['working'] += $working ? 1 : 0;
            $summary['present'] += $record?->clock_in ? 1 : 0;
            $summary['late'] += $record?->status === 'late' ? 1 : 0;
            $summary['early'] += $record?->left_early ? 1 : 0;
            $summary['off_site'] += $record?->clock_in_off_site ? 1 : 0;
            $summary['minutes'] += (int) $record?->worked_minutes;
            $summary['leave'] += $working && $leave && ! $record ? 1 : 0;
            $summary['missing'] += $missing ? 1 : 0;

            $days[] = ['date' => $day->copy(), 'record' => $record, 'working' => $working, 'leave' => $leave, 'missing' => $missing];
        }

        return view('print.timesheet', [
            'employee' => $employee,
            'month' => $start,
            'days' => $days,
            'summary' => $summary,
            'canViewAll' => $canViewAll,
            'employees' => $canViewAll
                ? Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
                : collect(),
        ]);
    }

    private function dailyRegister(Carbon $date): View
    {
        $date = $date->copy()->startOfDay();
        $working = in_array($date->dayOfWeekIso, config('hrpro.work.working_days', [1, 2, 3, 4, 5]), true);
        $isPast = $date->lt(now()->startOfDay());

        $records = Attendance::with(['employee.department', 'clockInLocation'])
            ->whereDate('work_date', $date)
            ->get()
            ->keyBy('employee_id');

        $leaves = $this->approvedLeaveBetween($date, $date)->get()->keyBy('employee_id');

        // Everyone still employed, plus anyone who punched that day and has since left.
        $employees = Employee::with('department')->active()->get();
        $leavers = $records->pluck('employee')->filter()
            ->reject(fn (Employee $e) => $employees->contains('id', $e->id));

        $rows = $employees->concat($leavers)
            ->map(function (Employee $employee) use ($records, $leaves, $working) {
                $record = $records->get($employee->id);
                $leave = $leaves->get($employee->id);

                return [
                    'employee' => $employee,
                    'record' => $record,
                    'leave' => $leave,
                    'missing' => $working && ! $record && ! $leave,
                ];
            })
            ->sortBy([
                fn ($a, $b) => strcmp($a['employee']->department?->name ?? "\u{10FFFF}", $b['employee']->department?->name ?? "\u{10FFFF}"),
                fn ($a, $b) => strcmp($a['employee']->full_name, $b['employee']->full_name),
            ])
            ->values();

        return view('print.attendance-daily', [
            'date' => $date,
            'working' => $working,
            'isPast' => $isPast,
            'rows' => $rows,
            'summary' => [
                'employees' => $rows->count(),
                'present' => $rows->filter(fn ($r) => $r['record']?->clock_in)->count(),
                'late' => $rows->filter(fn ($r) => $r['record']?->status === 'late')->count(),
                'early' => $rows->filter(fn ($r) => $r['record']?->left_early)->count(),
                'leave' => $rows->filter(fn ($r) => $r['leave'] && ! $r['record'])->count(),
                'missing' => $rows->where('missing', true)->count(),
                'off_site' => $rows->filter(fn ($r) => $r['record']?->clock_in_off_site)->count(),
            ],
        ]);
    }

    private function approvedLeaveBetween(Carbon $start, Carbon $end)
    {
        return LeaveRequest::with('leaveType')
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }
}
