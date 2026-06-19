<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboard): View
    {
        return view('dashboard.index', [
            'metrics' => $dashboard->metrics(),
            'headcount' => $dashboard->headcountByDepartment(),
            'recentLeaves' => $dashboard->recentLeaveRequests(),
            'announcements' => $dashboard->latestAnnouncements(),
            'newHires' => $dashboard->newHires(),
            'myEmployee' => auth()->user()->employee,
        ]);
    }
}
