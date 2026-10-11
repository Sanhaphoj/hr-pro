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
     * of approved unpaid leave that fall inside the month) − the social
     * security contribution when it is switched on in Settings → Payroll.
     * Idempotent: skips employees that already have a row for the period.
     *
     * @return int number of payroll rows created
     */
    public function generateForPeriod(int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $daysPerMonth = max(1, (int) config('hrpro.payroll.days_per_month', 30));

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

            $salary = (float) $employee->base_salary;
            $dailyRate = $salary / $daysPerMonth;

            $unpaidDays = (float) LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->sum('total_days');

            $unpaid = round($dailyRate * $unpaidDays, 2);
            $socialSecurity = $this->socialSecurity($salary);
            $deductions = round($unpaid + $socialSecurity, 2);
            $net = round($salary - $deductions, 2);

            $note = array_filter([
                $unpaidDays > 0 ? "หักลาไม่รับค่าจ้าง {$unpaidDays} วัน" : null,
                $socialSecurity > 0 ? 'ประกันสังคม '.number_format($socialSecurity, 2).' บาท' : null,
            ]);

            Payroll::create([
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
                'base_salary' => $employee->base_salary,
                'allowances' => 0,
                'deductions' => $deductions,
                'net_pay' => $net,
                'status' => 'draft',
                'note' => $note ? implode(' · ', $note) : null,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Employee social security contribution: the rate applied to the salary,
     * up to the wage cap (0 = no cap).
     */
    public function socialSecurity(float $salary): float
    {
        if (! config('hrpro.payroll.social_security.enabled', false)) {
            return 0.0;
        }

        $rate = (float) config('hrpro.payroll.social_security.rate', 5);
        $cap = (float) config('hrpro.payroll.social_security.wage_cap', 15000);
        $base = $cap > 0 ? min($salary, $cap) : $salary;

        return round($base * $rate / 100, 2);
    }
}
