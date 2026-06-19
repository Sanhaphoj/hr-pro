<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
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
}
