<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(AttendanceService $attendanceService): View
    {
        $user = auth()->user();
        $employee = $user->employee;
        $canViewAll = $user->hasPermission('attendance.viewAll');

        // Today's attendance for the self-service clock card.
        $today = $employee ? $attendanceService->todayFor($employee) : null;

        // History list — all employees for managers, otherwise the user's own records.
        $records = null;

        if ($canViewAll) {
            $query = Attendance::query()->with('employee');

            if ($date = request('date')) {
                $query->whereDate('work_date', $date);
            }

            $records = $query
                ->orderByDesc('work_date')
                ->orderByDesc('clock_in')
                ->paginate(config('hrpro.per_page'))
                ->withQueryString();
        } elseif ($employee) {
            $records = Attendance::where('employee_id', $employee->id)
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

    public function clockIn(AttendanceService $attendanceService): RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('attendance.index')->with('error', 'ยังไม่มีโปรไฟล์พนักงานที่เชื่อมโยงกับบัญชีของคุณ');
        }

        try {
            $attendance = $attendanceService->clockIn($employee);
        } catch (ValidationException $e) {
            return redirect()->route('attendance.index')->with('error', $e->validator->errors()->first());
        }

        AuditLogger::log('created', 'ลงเวลาเข้างาน', $attendance);

        return redirect()->route('attendance.index')->with('success', 'ลงเวลาเข้างานเรียบร้อย');
    }

    public function clockOut(AttendanceService $attendanceService): RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('attendance.index')->with('error', 'ยังไม่มีโปรไฟล์พนักงานที่เชื่อมโยงกับบัญชีของคุณ');
        }

        try {
            $attendance = $attendanceService->clockOut($employee);
        } catch (ValidationException $e) {
            return redirect()->route('attendance.index')->with('error', $e->validator->errors()->first());
        }

        AuditLogger::log('updated', 'ลงเวลาออกงาน', $attendance);

        return redirect()->route('attendance.index')->with('success', 'ลงเวลาออกงานเรียบร้อย');
    }
}
