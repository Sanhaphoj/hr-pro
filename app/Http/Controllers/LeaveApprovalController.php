<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LeaveApprovalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:leave-approvals.view', only: ['index']),
            new Middleware('permission:leave-approvals.approve', only: ['approve', 'reject']),
        ];
    }

    public function index(): View
    {
        $requests = LeaveRequest::query()
            ->pending()
            ->with(['employee.department', 'leaveType'])
            ->orderBy('start_date')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('leave-approvals.index', [
            'requests' => $requests,
        ]);
    }

    public function approve(LeaveRequest $leaveRequest, LeaveService $leaveService, NotificationService $notifications): RedirectResponse
    {
        // ValidationException auto-redirects back with errors.
        $leaveService->approve($leaveRequest, auth()->user());

        $employeeUser = $leaveRequest->employee?->user;
        if ($employeeUser) {
            $notifications->notify(
                $employeeUser,
                'อนุมัติการลา',
                'คำขอลาของคุณได้รับการอนุมัติแล้ว',
                'success',
                route('leave-requests.show', $leaveRequest),
            );
        }

        AuditLogger::log('approved', 'อนุมัติคำขอลางาน', $leaveRequest);

        return redirect()->back()->with('success', 'อนุมัติคำขอลาเรียบร้อยแล้ว');
    }

    public function reject(RejectLeaveRequest $request, LeaveRequest $leaveRequest, LeaveService $leaveService, NotificationService $notifications): RedirectResponse
    {
        $reason = $request->validated()['reason'];

        // ValidationException auto-redirects back with errors.
        $leaveService->reject($leaveRequest, auth()->user(), $reason);

        $employeeUser = $leaveRequest->employee?->user;
        if ($employeeUser) {
            $notifications->notify(
                $employeeUser,
                'ปฏิเสธการลา',
                'คำขอลาของคุณไม่ได้รับการอนุมัติ',
                'error',
                route('leave-requests.show', $leaveRequest),
            );
        }

        AuditLogger::log('rejected', 'ปฏิเสธคำขอลางาน', $leaveRequest);

        return redirect()->back()->with('success', 'ปฏิเสธคำขอลาแล้ว');
    }
}
