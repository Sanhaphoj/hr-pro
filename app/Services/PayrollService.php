<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Support\Carbon;

class PayrollService
{
    /**
     * Generate draft payroll rows for every salaried employee for the given
     * period. Net pay = base salary − unpaid-leave deduction (daily rate × days
     * of approved unpaid leave that fall inside the month). Idempotent: skips
     * employees that already have a row for the period.
     *
     * @return int number of payroll rows created
     */
    public function generateForPeriod(int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $employees = Employee::query()
            ->whereNotNull('base_salary')
            ->where('base_salary', '>', 0)
            ->get();

        $created = 0;

        foreach ($employees as $employee) {
            $already = Payroll::forPeriod($year, $month)->where('employee_id', $employee->id)->exists();
            if ($already) {
                continue;
            }

            $dailyRate = (float) $employee->base_salary / 30;

            $unpaidDays = (float) LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->sum('total_days');

            $deductions = round($dailyRate * $unpaidDays, 2);
            $net = round((float) $employee->base_salary - $deductions, 2);

            Payroll::create([
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
                'base_salary' => $employee->base_salary,
                'allowances' => 0,
                'deductions' => $deductions,
                'net_pay' => $net,
                'status' => 'draft',
                'note' => $unpaidDays > 0 ? "หักลาไม่รับค่าจ้าง {$unpaidDays} วัน" : null,
            ]);

            $created++;
        }

        return $created;
    }
}
