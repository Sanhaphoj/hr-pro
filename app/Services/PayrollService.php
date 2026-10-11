<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Support\Carbon;

/**
 * Monthly pay under Thai rules.
 *
 *   earnings   = base salary + allowances + overtime
 *   deductions = unpaid leave + social security + provident fund + withholding tax
 *   net pay    = earnings − deductions
 *
 * Every rate and switch lives in Settings → Payroll. A period that has
 * already been generated is never changed by a settings change; a draft
 * slip is recalculated only when someone edits it.
 */
class PayrollService
{
    /**
     * Personal-income-tax brackets (Revenue Code s.48): upper bound of
     * net taxable income => rate. The last bound is open-ended.
     */
    public const TAX_BRACKETS = [
        150000 => 0,
        300000 => 5,
        500000 => 10,
        750000 => 15,
        1000000 => 20,
        2000000 => 25,
        5000000 => 30,
        PHP_INT_MAX => 35,
    ];

    /**
     * Generate draft payroll rows for every salaried employee for the given
     * period. Idempotent: skips employees that already have a row for it.
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
            if (Payroll::forPeriod($year, $month)->where('employee_id', $employee->id)->exists()) {
                continue;
            }

            $salary = (float) $employee->base_salary;

            $unpaidDays = (float) LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->sum('total_days');

            $items = $this->compute($salary, round($this->dailyRate($salary) * $unpaidDays, 2));

            Payroll::create([
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
                'base_salary' => $salary,
                'status' => 'draft',
                'note' => $unpaidDays > 0 ? "หักลาไม่รับค่าจ้าง {$unpaidDays} วัน" : null,
            ] + $items);

            $created++;
        }

        return $created;
    }

    /**
     * Re-run a draft slip after overtime hours or allowances were changed.
     * The unpaid-leave deduction found at generation is kept.
     */
    public function recalculate(Payroll $payroll, float $overtimeHours, float $allowances): Payroll
    {
        $payroll->update($this->compute(
            (float) $payroll->base_salary,
            (float) $payroll->unpaid_leave_deduction,
            $allowances,
            $overtimeHours,
        ));

        return $payroll;
    }

    /**
     * Every amount on a slip from its inputs.
     *
     * @return array<string, float>
     */
    public function compute(float $salary, float $unpaid = 0, float $allowances = 0, float $overtimeHours = 0): array
    {
        $overtimePay = $this->overtimePay($salary, $overtimeHours);
        // What was actually earned for work this month: the wage for SSO and tax.
        $wage = max(0, $salary - $unpaid + $allowances + $overtimePay);

        $socialSecurity = $this->socialSecurity($wage);
        $provident = $this->providentFund($salary);
        $tax = $this->withholdingTax($wage, $socialSecurity, $provident);

        $deductions = round($unpaid + $socialSecurity + $provident + $tax, 2);

        return [
            'allowances' => round($allowances, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_pay' => $overtimePay,
            'unpaid_leave_deduction' => round($unpaid, 2),
            'social_security' => $socialSecurity,
            'provident_fund' => $provident,
            'withholding_tax' => $tax,
            'deductions' => $deductions,
            'net_pay' => round($salary + $allowances + $overtimePay - $deductions, 2),
        ];
    }

    public function dailyRate(float $salary): float
    {
        return $salary / max(1, (int) config('hrpro.payroll.days_per_month', 30));
    }

    /**
     * Overtime: hourly wage (monthly ÷ days ÷ hours) × hours × the rate —
     * 1.5× on a working day under the Labour Protection Act s.61.
     */
    public function overtimePay(float $salary, float $hours): float
    {
        if ($hours <= 0) {
            return 0.0;
        }

        $hourly = $this->dailyRate($salary) / max(1, (float) config('hrpro.payroll.hours_per_day', 8));

        return round($hourly * $hours * (float) config('hrpro.payroll.overtime_rate', 1.5), 2);
    }

    /**
     * Employee social security contribution: the rate applied to the wage,
     * up to the wage cap (0 = no cap).
     */
    public function socialSecurity(float $wage): float
    {
        if (! config('hrpro.payroll.social_security.enabled', false)) {
            return 0.0;
        }

        $rate = (float) config('hrpro.payroll.social_security.rate', 5);
        $cap = (float) config('hrpro.payroll.social_security.wage_cap', 15000);
        $base = $cap > 0 ? min($wage, $cap) : $wage;

        return round($base * $rate / 100, 2);
    }

    /**
     * Employee provident fund contribution, a percentage of base salary.
     */
    public function providentFund(float $salary): float
    {
        if (! config('hrpro.payroll.provident_fund.enabled', false)) {
            return 0.0;
        }

        return round($salary * (float) config('hrpro.payroll.provident_fund.rate', 3) / 100, 2);
    }

    /**
     * Monthly withholding tax, by the Revenue Department's averaging method:
     * this month's taxable pay is assumed for all twelve months, the annual
     * tax is worked out, and one twelfth is withheld.
     *
     * Deductions applied: employment expenses (50%, at most 100,000), the
     * personal allowance, and the year's social security and provident fund
     * contributions. Other allowances (spouse, children, insurance…) are
     * claimed by the employee on their own return and are not estimated here.
     */
    public function withholdingTax(float $monthlyWage, float $monthlySso = 0, float $monthlyProvident = 0): float
    {
        if (! config('hrpro.payroll.withholding_tax.enabled', true) || $monthlyWage <= 0) {
            return 0.0;
        }

        $annual = $monthlyWage * 12;
        $expenses = min($annual * 0.5, 100000);
        $personal = (float) config('hrpro.payroll.withholding_tax.personal_allowance', 60000);
        $sso = min($monthlySso * 12, 9000);
        // Provident fund is deductible up to 15% of wages and 500,000 a year.
        $provident = min($monthlyProvident * 12, $annual * 0.15, 500000);

        $taxable = max(0, $annual - $expenses - $personal - $sso - $provident);

        return round($this->annualTax($taxable) / 12, 2);
    }

    /**
     * Progressive tax on a year's net taxable income.
     */
    public function annualTax(float $taxable): float
    {
        $tax = 0.0;
        $floor = 0;

        foreach (self::TAX_BRACKETS as $ceiling => $rate) {
            if ($taxable <= $floor) {
                break;
            }
            $tax += (min($taxable, $ceiling) - $floor) * $rate / 100;
            $floor = $ceiling;
        }

        return round($tax, 2);
    }
}
