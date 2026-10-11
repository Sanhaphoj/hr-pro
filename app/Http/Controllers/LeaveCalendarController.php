<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Who is away, month by month.
 *
 * Anyone may see when their own department colleagues are off, since that
 * is what planning work around them needs, but only as "on leave": the
 * kind of leave (sick, personal…) is shown just to the person themself and
 * to roles that already read every request (leave-requests.viewAll), who
 * can also look across departments and see what is still awaiting approval.
 */
class LeaveCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $me = $user->employee;
        $viewAll = $user->hasPermission('leave-requests.viewAll');

        $month = rescue(fn () => Carbon::createFromFormat('Y-m', (string) $request->query('month'))->startOfMonth(),
            now()->startOfMonth(), false);
        $first = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $last = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $departmentId = $viewAll
            ? ($request->integer('department') ?: null)
            : $me?->department_id;

        $leaves = LeaveRequest::query()
            ->with(['employee:id,first_name,last_name,email,department_id', 'leaveType:id,name,color'])
            ->whereIn('status', $viewAll
                ? [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_PENDING]
                : [LeaveRequest::STATUS_APPROVED])
            ->whereDate('start_date', '<=', $last)
            ->whereDate('end_date', '>=', $first)
            // Everyone, when allowed and unfiltered; otherwise the department
            // (none on file means only one's own leave) plus one's own.
            ->when($departmentId || ! $viewAll, fn ($query) => $query->where(function ($q) use ($departmentId, $me) {
                $departmentId
                    ? $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId))
                    : $q->whereRaw('1 = 0');
                if ($me) {
                    $q->orWhere('employee_id', $me->id);
                }
            }))
            ->orderBy('start_date')
            ->get();

        // Pending requests of one's own count too: they are one's own plan.
        if ($me && ! $viewAll) {
            $leaves = $leaves->merge(
                LeaveRequest::query()
                    ->with(['employee:id,first_name,last_name,email,department_id', 'leaveType:id,name,color'])
                    ->where('employee_id', $me->id)
                    ->where('status', LeaveRequest::STATUS_PENDING)
                    ->whereDate('start_date', '<=', $last)
                    ->whereDate('end_date', '>=', $first)
                    ->get()
            );
        }

        $leaves = $leaves->unique('id')->filter(fn ($leave) => $leave->employee);

        $workingDays = (array) config('hrpro.work.working_days', [1, 2, 3, 4, 5]);
        $days = [];
        for ($day = $first->copy(); $day->lte($last); $day->addDay()) {
            $date = $day->copy();
            $isWorkday = in_array($date->dayOfWeekIso, $workingDays, true);
            $days[] = [
                'date' => $date,
                'inMonth' => $date->month === $month->month,
                'isToday' => $date->isToday(),
                'isWorkday' => $isWorkday,
                // A leave across a weekend does not use the days off in between.
                'leaves' => $leaves
                    ->filter(fn ($leave) => $isWorkday && $date->betweenIncluded($leave->start_date, $leave->end_date))
                    ->map(fn ($leave) => [
                        'name' => $leave->employee->full_name,
                        'initials' => $leave->employee->initials,
                        'email' => $leave->employee->email,
                        'mine' => $me && $leave->employee_id === $me->id,
                        'type' => ($viewAll || ($me && $leave->employee_id === $me->id)) ? $leave->leaveType?->name : null,
                        'color' => ($viewAll || ($me && $leave->employee_id === $me->id)) ? ($leave->leaveType?->color ?? 'blue') : 'gray',
                        'pending' => $leave->status === LeaveRequest::STATUS_PENDING,
                        'url' => ($viewAll || ($me && $leave->employee_id === $me->id)) ? route('leave-requests.show', $leave) : null,
                    ])
                    ->values(),
            ];
        }

        return view('leave-calendar.index', [
            'month' => $month,
            'weeks' => array_chunk($days, 7),
            'viewAll' => $viewAll,
            'departments' => $viewAll ? Department::orderBy('name')->get(['id', 'name']) : collect(),
            'departmentId' => $departmentId,
            'awayToday' => $leaves->filter(fn ($leave) => $leave->status === LeaveRequest::STATUS_APPROVED
                && now()->startOfDay()->betweenIncluded($leave->start_date, $leave->end_date))->count(),
        ]);
    }
}
