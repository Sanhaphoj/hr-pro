<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    /**
     * Count working days between two dates (inclusive), honouring the company
     * working-day configuration. Weekends/holidays are not charged as leave.
     */
    public function workingDaysBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        if ($end->lessThan($start)) {
            return 0.0;
        }

        $workingDays = config('hrpro.work.working_days', [1, 2, 3, 4, 5]);
        $count = 0;
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            if (in_array($cursor->dayOfWeekIso, $workingDays, true)) {
                $count++;
            }
            $cursor->addDay();
        }

        return (float) $count;
    }

    /**
     * Fetch (or lazily create) an employee's balance row for a leave type/year.
     */
    public function ensureBalance(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
            ['entitled_days' => $type->days_per_year, 'used_days' => 0, 'pending_days' => 0],
        );
    }

    /**
     * Does the employee already have a pending/approved request overlapping the range?
     */
    public function hasOverlap(Employee $employee, CarbonInterface $start, CarbonInterface $end, ?int $exceptId = null): bool
    {
        return LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
    }

    /**
     * Submit a leave request, enforcing all business rules atomically.
     *
     * @throws ValidationException
     */
    public function createRequest(Employee $employee, array $data): LeaveRequest
    {
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $totalDays = $this->workingDaysBetween($start, $end);

        if ($totalDays <= 0) {
            throw ValidationException::withMessages([
                'start_date' => 'ช่วงวันที่ที่เลือกไม่มีวันทำงาน (อาจตรงกับวันหยุดสุดสัปดาห์)',
            ]);
        }

        if ($this->hasOverlap($employee, $start, $end)) {
            throw ValidationException::withMessages([
                'start_date' => 'มีคำขอลาที่ทับซ้อนกับช่วงวันที่นี้อยู่แล้ว',
            ]);
        }

        $year = $start->year;
        $balance = $this->ensureBalance($employee, $type, $year);

        if ($type->days_per_year > 0 && $totalDays > $balance->remaining_days) {
            throw ValidationException::withMessages([
                'leave_type_id' => "วันลาคงเหลือไม่เพียงพอ (คงเหลือ {$balance->remaining_days} วัน, ต้องการ {$totalDays} วัน)",
            ]);
        }

        return DB::transaction(function () use ($employee, $type, $start, $end, $totalDays, $balance, $data) {
            $autoApprove = ! $type->requires_approval;

            $request = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'status' => $autoApprove ? LeaveRequest::STATUS_APPROVED : LeaveRequest::STATUS_PENDING,
                'approved_at' => $autoApprove ? now() : null,
                'attachment_path' => $data['attachment_path'] ?? null,
            ]);

            if ($autoApprove) {
                $balance->used_days = (float) $balance->used_days + $totalDays;
            } else {
                $balance->pending_days = (float) $balance->pending_days + $totalDays;
            }
            $balance->save();

            return $request;
        });
    }

    /**
     * Approve a pending request and move days from "pending" to "used".
     */
    public function approve(LeaveRequest $request, User $approver): LeaveRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => 'คำขอนี้ได้รับการดำเนินการไปแล้ว']);
        }

        return DB::transaction(function () use ($request, $approver) {
            $balance = $this->ensureBalance($request->employee, $request->leaveType, $request->start_date->year);
            $balance->pending_days = max(0, (float) $balance->pending_days - (float) $request->total_days);
            $balance->used_days = (float) $balance->used_days + (float) $request->total_days;
            $balance->save();

            $request->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'approver_id' => $approver->id,
                'approved_at' => now(),
            ]);

            return $request;
        });
    }

    /**
     * Reject a pending request and release the pending days.
     */
    public function reject(LeaveRequest $request, User $approver, string $reason): LeaveRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => 'คำขอนี้ได้รับการดำเนินการไปแล้ว']);
        }

        return DB::transaction(function () use ($request, $approver, $reason) {
            $balance = $this->ensureBalance($request->employee, $request->leaveType, $request->start_date->year);
            $balance->pending_days = max(0, (float) $balance->pending_days - (float) $request->total_days);
            $balance->save();

            $request->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'approver_id' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $request;
        });
    }

    /**
     * Cancel a request and return the reserved/used days to the balance.
     */
    public function cancel(LeaveRequest $request): LeaveRequest
    {
        if (in_array($request->status, [LeaveRequest::STATUS_CANCELLED, LeaveRequest::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages(['status' => 'ไม่สามารถยกเลิกคำขอนี้ได้']);
        }

        return DB::transaction(function () use ($request) {
            $balance = $this->ensureBalance($request->employee, $request->leaveType, $request->start_date->year);

            if ($request->status === LeaveRequest::STATUS_PENDING) {
                $balance->pending_days = max(0, (float) $balance->pending_days - (float) $request->total_days);
            } elseif ($request->status === LeaveRequest::STATUS_APPROVED) {
                $balance->used_days = max(0, (float) $balance->used_days - (float) $request->total_days);
            }
            $balance->save();

            $request->update(['status' => LeaveRequest::STATUS_CANCELLED]);

            return $request;
        });
    }
}
