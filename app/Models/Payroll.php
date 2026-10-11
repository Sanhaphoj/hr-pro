<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id', 'period_year', 'period_month',
        'base_salary', 'allowances', 'deductions', 'net_pay',
        'overtime_hours', 'overtime_pay', 'unpaid_leave_deduction',
        'social_security', 'provident_fund', 'withholding_tax',
        'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'unpaid_leave_deduction' => 'decimal:2',
            'social_security' => 'decimal:2',
            'provident_fund' => 'decimal:2',
            'withholding_tax' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    /** Salary + allowances + overtime. */
    public function getEarningsAttribute(): float
    {
        return (float) $this->base_salary + (float) $this->allowances + (float) $this->overtime_pay;
    }

    /**
     * Deduction lines for the slip, largest kinds first; a total recorded
     * before deductions were itemised shows as one "other" line.
     *
     * @return array<string, float>
     */
    public function deductionLines(): array
    {
        $lines = array_filter([
            'ภาษีเงินได้หัก ณ ที่จ่าย' => (float) $this->withholding_tax,
            'เงินสมทบประกันสังคม' => (float) $this->social_security,
            'เงินสะสมกองทุนสำรองเลี้ยงชีพ' => (float) $this->provident_fund,
            'ลาไม่รับค่าจ้าง / ขาดงาน' => (float) $this->unpaid_leave_deduction,
        ], fn ($amount) => $amount > 0);

        $other = round((float) $this->deductions - array_sum($lines), 2);
        if ($other > 0) {
            $lines['รายการหักอื่น ๆ'] = $other;
        }

        return $lines;
    }

    /**
     * The employee's totals from January up to and including this period.
     *
     * @return array{count: int, earnings: float, deductions: float, net: float, tax: float, sso: float}
     */
    public function yearToDate(): array
    {
        $ytd = static::query()
            ->where('employee_id', $this->employee_id)
            ->where('period_year', $this->period_year)
            ->where('period_month', '<=', $this->period_month)
            // Aliased ytd_*: a plain "earnings" would be read through this
            // model's earnings accessor instead of the query's column.
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(base_salary + allowances + overtime_pay), 0) as ytd_earnings,
                COALESCE(SUM(deductions), 0) as ded, COALESCE(SUM(net_pay), 0) as net,
                COALESCE(SUM(withholding_tax), 0) as tax, COALESCE(SUM(social_security), 0) as sso')
            ->first();

        return [
            'count' => (int) $ytd->cnt,
            'earnings' => (float) $ytd->ytd_earnings,
            'deductions' => (float) $ytd->ded,
            'net' => (float) $ytd->net,
            'tax' => (float) $ytd->tax,
            'sso' => (float) $ytd->sso,
        ];
    }

    public function getPeriodLabelAttribute(): string
    {
        return Carbon::create($this->period_year, $this->period_month, 1)->translatedFormat('F Y');
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }
}
