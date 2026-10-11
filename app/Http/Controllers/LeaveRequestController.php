<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * Self-service leave requests. Authenticated users only — data is scoped
     * in the controller (no route-level permission middleware).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $employee = $user->employee;
        $viewAll = $user->hasPermission('leave-requests.viewAll');

        $statuses = [
            LeaveRequest::STATUS_PENDING => 'รออนุมัติ',
            LeaveRequest::STATUS_APPROVED => 'อนุมัติแล้ว',
            LeaveRequest::STATUS_REJECTED => 'ไม่อนุมัติ',
            LeaveRequest::STATUS_CANCELLED => 'ยกเลิก',
        ];
        $status = $request->query('status');

        $query = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->when($status && array_key_exists($status, $statuses), fn ($q) => $q->where('status', $status))
            ->latest('start_date');

        if ($viewAll) {
            // Sees every request across the organisation.
        } elseif ($employee) {
            $query->where('employee_id', $employee->id);
        } else {
            // No linked employee and no global access — nothing to show.
            $query->whereRaw('1 = 0');
        }

        $requests = $query->paginate(config('hrpro.per_page'))->withQueryString();

        return view('leave-requests.index', [
            'requests' => $requests,
            'employee' => $employee,
            'viewAll' => $viewAll,
            'statuses' => $statuses,
            'status' => $status,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('leave-requests.index')
                ->with('error', 'ยังไม่มีโปรไฟล์พนักงานที่เชื่อมโยงกับบัญชีของคุณ');
        }

        $year = now()->year;
        $service = app(LeaveService::class);

        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        $balances = $leaveTypes->map(function (LeaveType $type) use ($employee, $year, $service) {
            return [
                'type' => $type,
                'balance' => $service->ensureBalance($employee, $type, $year),
            ];
        });

        $typeOptions = $leaveTypes->pluck('name', 'id');

        return view('leave-requests.create', [
            'employee' => $employee,
            'typeOptions' => $typeOptions,
            'balances' => $balances,
            'year' => $year,
        ]);
    }

    public function store(StoreLeaveRequestRequest $request, LeaveService $leaveService): RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('leave-requests.index')
                ->with('error', 'ยังไม่มีโปรไฟล์พนักงานที่เชื่อมโยงกับบัญชีของคุณ');
        }

        $data = $request->validated();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave-attachments', 'public');
        }

        // ValidationException thrown by the service auto-redirects back with errors.
        $leaveRequest = $leaveService->createRequest($employee, [
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            // The column is required; a reason can be optional in Settings → Leave.
            'reason' => $data['reason'] ?? '',
            'attachment_path' => $attachmentPath,
        ]);

        AuditLogger::log('created', 'ส่งคำขอลางาน', $leaveRequest);

        return redirect()->route('leave-requests.index')
            ->with('success', 'ส่งคำขอลาเรียบร้อย รอการอนุมัติ');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $isOwner = $this->authorizeViewing($leaveRequest);

        $leaveRequest->load(['employee.department', 'leaveType', 'approver']);

        return view('leave-requests.show', [
            'leaveRequest' => $leaveRequest,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * The request as a signed leave form, with this year's usage of the type.
     * Read-only: the balance row is looked up, never created, from here.
     */
    public function print(LeaveRequest $leaveRequest): View
    {
        $this->authorizeViewing($leaveRequest);

        $leaveRequest->load(['employee.department', 'employee.position', 'employee.manager', 'leaveType', 'approver']);

        $year = $leaveRequest->start_date->year;
        $balance = LeaveBalance::query()
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        // Approved leave of the same type taken earlier in the year.
        $takenBefore = (float) LeaveRequest::query()
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->whereDate('start_date', '<', $leaveRequest->start_date)
            ->whereKeyNot($leaveRequest->getKey())
            ->sum('total_days');

        return view('print.leave-request', [
            'leaveRequest' => $leaveRequest,
            'balance' => $balance,
            'takenBefore' => $takenBefore,
        ]);
    }

    public function cancel(LeaveRequest $leaveRequest, LeaveService $leaveService): RedirectResponse
    {
        $this->authorizeViewing($leaveRequest);

        // ValidationException auto-redirects back with errors.
        $leaveService->cancel($leaveRequest);

        AuditLogger::log('updated', 'ยกเลิกคำขอลางาน', $leaveRequest);

        return redirect()->back()->with('success', 'ยกเลิกคำขอลาแล้ว');
    }

    /**
     * The request's own employee, or someone who may see everyone's requests.
     * Anyone else is refused. Returns whether the viewer is the owner.
     */
    private function authorizeViewing(LeaveRequest $leaveRequest): bool
    {
        $user = auth()->user();
        $isOwner = $leaveRequest->employee && $leaveRequest->employee->user_id === $user->id;

        abort_unless($isOwner || $user->hasPermission('leave-requests.viewAll'), 403);

        return $isOwner;
    }
}
