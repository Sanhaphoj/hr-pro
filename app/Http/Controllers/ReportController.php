<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.view', only: ['index']),
        ];
    }

    public function index(): View
    {
        $year = now()->year;
        $today = now()->toDateString();

        // --- Headcount by department -----------------------------------------
        $headcountByDepartment = Department::query()
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->orderBy('name')
            ->get();

        // --- Headcount by employment status ----------------------------------
        $statusLabels = [
            'active' => 'ทำงานปกติ',
            'probation' => 'ทดลองงาน',
            'on_leave' => 'กำลังลา',
            'suspended' => 'พักงาน',
            'terminated' => 'พ้นสภาพ',
        ];

        $statusCounts = Employee::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $headcountByStatus = collect(Employee::STATUSES)->map(fn (string $status) => [
            'key' => $status,
            'label' => $statusLabels[$status] ?? $status,
            'count' => (int) ($statusCounts[$status] ?? 0),
        ]);

        // --- Headcount by employment type ------------------------------------
        $employmentLabels = [
            'full_time' => 'พนักงานประจำ',
            'part_time' => 'พาร์ทไทม์',
            'contract' => 'สัญญาจ้าง',
            'intern' => 'นักศึกษาฝึกงาน',
        ];

        $employmentTypeCounts = Employee::query()
            ->selectRaw('employment_type, COUNT(*) as total')
            ->groupBy('employment_type')
            ->pluck('total', 'employment_type');

        $headcountByEmploymentType = collect(Employee::EMPLOYMENT_TYPES)->map(fn (string $type) => [
            'key' => $type,
            'label' => $employmentLabels[$type] ?? $type,
            'count' => (int) ($employmentTypeCounts[$type] ?? 0),
        ]);

        $totalEmployees = (int) $statusCounts->sum();

        // --- Leave usage this year (approved) by leave type ------------------
        $leaveUsageRaw = LeaveRequest::query()
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('leave_requests.status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('leave_requests.start_date', $year)
            ->groupBy('leave_types.id', 'leave_types.name', 'leave_types.color')
            ->selectRaw('leave_types.id as id, leave_types.name as name, leave_types.color as color, SUM(leave_requests.total_days) as total_days, COUNT(*) as request_count')
            ->orderByDesc('total_days')
            ->get();

        // Ensure every active leave type appears even when it has no usage yet.
        $leaveTypes = LeaveType::query()->orderBy('name')->get(['id', 'name', 'color']);
        $leaveUsage = $leaveTypes->map(function (LeaveType $type) use ($leaveUsageRaw) {
            $row = $leaveUsageRaw->firstWhere('id', $type->id);

            return [
                'name' => $type->name,
                'color' => $type->color,
                'total_days' => (float) ($row->total_days ?? 0),
                'request_count' => (int) ($row->request_count ?? 0),
            ];
        })->sortByDesc('total_days')->values();

        $leaveUsageTotalDays = (float) $leaveUsage->sum('total_days');

        // --- Attendance summary today ----------------------------------------
        $attendanceTodayCounts = Attendance::query()
            ->whereDate('work_date', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attendanceToday = [
            'present' => (int) ($attendanceTodayCounts['present'] ?? 0),
            'late' => (int) ($attendanceTodayCounts['late'] ?? 0),
            'absent' => (int) ($attendanceTodayCounts['absent'] ?? 0),
            'half_day' => (int) ($attendanceTodayCounts['half_day'] ?? 0),
            'on_leave' => (int) ($attendanceTodayCounts['on_leave'] ?? 0),
        ];
        $attendanceToday['total'] = array_sum($attendanceToday);

        return view('reports.index', [
            'year' => $year,
            'totalEmployees' => $totalEmployees,
            'headcountByDepartment' => $headcountByDepartment,
            'headcountByStatus' => $headcountByStatus,
            'headcountByEmploymentType' => $headcountByEmploymentType,
            'leaveUsage' => $leaveUsage,
            'leaveUsageTotalDays' => $leaveUsageTotalDays,
            'attendanceToday' => $attendanceToday,
        ]);
    }
}
