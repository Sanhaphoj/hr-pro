<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

/**
 * One dashboard, assembled per person: everyone gets their own day, leads
 * get their team, and organisation-wide figures appear only for the roles
 * that may already read them elsewhere. Nothing is computed for a panel the
 * viewer will not see.
 */
class DashboardController extends Controller
{
    public function index(DashboardService $dashboard): View
    {
        $user = auth()->user();
        $me = $user->employee;

        $can = [
            'org' => $user->hasPermission('employees.view'),
            'approve' => $user->hasPermission('leave-approvals.view'),
            'reports' => $user->hasPermission('reports.view'),
        ];

        return view('dashboard.index', [
            'can' => $can,
            'myEmployee' => $me,
            'personal' => $me ? $dashboard->personal($me) : null,
            'team' => $me ? $dashboard->teamToday($me) : collect(),
            'metrics' => $can['org'] ? $dashboard->metrics() : null,
            'headcount' => $can['org'] ? $dashboard->headcountByDepartment() : collect(),
            'trend' => $can['reports'] ? $dashboard->attendanceTrend() : [],
            'leaveByType' => $can['reports'] ? $dashboard->leaveDaysByType() : collect(),
            'recentLeaves' => $can['approve'] ? $dashboard->recentLeaveRequests() : collect(),
            'newHires' => $can['org'] ? $dashboard->newHires() : collect(),
            'celebrations' => $dashboard->celebrations(),
            'announcements' => $dashboard->latestAnnouncements(),
        ]);
    }
}
