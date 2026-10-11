<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:employees.view', only: ['employees']),
            new Middleware('permission:payroll.view', only: ['payroll', 'pnd1', 'socialSecurity']),
        ];
    }

    /**
     * Export the employee register as a UTF-8 CSV (opens directly in Excel).
     * No external library required — streamed via PHP's fputcsv.
     */
    public function employees(): StreamedResponse
    {
        $headers = ['รหัสพนักงาน', 'ชื่อ', 'นามสกุล', 'อีเมล', 'แผนก', 'ตำแหน่ง', 'สถานะ', 'วันเริ่มงาน'];

        AuditLogger::log('exported', 'ส่งออกทะเบียนพนักงาน (CSV)');

        return $this->streamCsv('employees-'.now()->format('Ymd').'.csv', $headers, function ($out) {
            Employee::with(['department:id,name', 'position:id,title'])
                ->orderBy('first_name')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $e) {
                        fputcsv($out, [
                            $e->employee_code,
                            $e->first_name,
                            $e->last_name,
                            $e->email,
                            $e->department?->name ?? '',
                            $e->position?->title ?? '',
                            $e->status,
                            optional($e->hire_date)->format('Y-m-d'),
                        ]);
                    }
                });
        });
    }

    /**
     * Export a payroll period as CSV.
     */
    public function payroll(Request $request): StreamedResponse
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $headers = ['รหัสพนักงาน', 'ชื่อ-นามสกุล', 'งวด', 'เงินเดือนฐาน', 'เบี้ยเลี้ยง', 'ชั่วโมง OT', 'ค่าล่วงเวลา',
            'ลาไม่รับค่าจ้าง', 'ประกันสังคม', 'กองทุนสำรองเลี้ยงชีพ', 'ภาษีหัก ณ ที่จ่าย', 'รวมหัก', 'สุทธิ', 'สถานะ'];

        AuditLogger::log('exported', "ส่งออกเงินเดือนงวด {$month}/{$year} (CSV)");

        return $this->streamCsv("payroll-{$year}-{$month}.csv", $headers, function ($out) use ($year, $month) {
            Payroll::with('employee:id,first_name,last_name,employee_code')
                ->forPeriod($year, $month)
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $p) {
                        fputcsv($out, [
                            $p->employee?->employee_code ?? '',
                            $p->employee?->full_name ?? '',
                            "{$p->period_month}/{$p->period_year}",
                            $p->base_salary,
                            $p->allowances,
                            $p->overtime_hours,
                            $p->overtime_pay,
                            $p->unpaid_leave_deduction,
                            $p->social_security,
                            $p->provident_fund,
                            $p->withholding_tax,
                            $p->deductions,
                            $p->net_pay,
                            $p->status,
                        ]);
                    }
                });
        });
    }

    /**
     * Attachment to ภ.ง.ด.1 (monthly withholding return for salaries, s.40(1))
     * for one period: every employee paid, with the income paid and the tax
     * withheld, ready to key into the Revenue Department's e-Filing.
     * Condition 1 = tax withheld at source.
     */
    public function pnd1(Request $request): StreamedResponse
    {
        [$year, $month] = $this->period($request);
        $periodDate = Carbon::create($year, $month, 1);
        $payDay = config('hrpro.payroll.pay_day');
        $paidOn = $payDay
            ? $periodDate->copy()->day(min((int) $payDay, $periodDate->daysInMonth))
            : $periodDate->copy()->endOfMonth();

        $headers = ['ลำดับที่', 'เลขประจำตัวผู้เสียภาษีอากร', 'ชื่อ', 'ชื่อสกุล', 'วัน เดือน ปี ที่จ่าย', 'จำนวนเงินได้ที่จ่าย', 'จำนวนเงินภาษีที่หักและนำส่ง', 'เงื่อนไข'];

        AuditLogger::log('exported', "ส่งออกใบแนบ ภ.ง.ด.1 งวด {$month}/{$year}");

        return $this->streamCsv(sprintf('PND1-%d-%02d.csv', $year + 543, $month), $headers, function ($out) use ($year, $month, $paidOn) {
            $i = 0;
            $income = 0.0;
            $tax = 0.0;
            $this->periodRows($year, $month, function (Payroll $p) use ($out, $paidOn, &$i, &$income, &$tax) {
                $earned = $this->wage($p);
                $income += $earned;
                $tax += (float) $p->withholding_tax;
                fputcsv($out, [
                    ++$i,
                    $this->digits($p->employee?->national_id),
                    $p->employee?->first_name ?? '',
                    $p->employee?->last_name ?? '',
                    sprintf('%02d/%02d/%d', $paidOn->day, $paidOn->month, $paidOn->year + 543),
                    $this->amount($earned),
                    $this->amount($p->withholding_tax),
                    1,
                ]);
            });
            fputcsv($out, ['', '', '', 'รวม', '', $this->amount($income), $this->amount($tax), '']);
        });
    }

    /**
     * สปส.1-10 part 2: each insured employee's wage (up to the cap) and
     * contribution for the month. The employer pays the same amount again,
     * shown under the total.
     */
    public function socialSecurity(Request $request): StreamedResponse
    {
        [$year, $month] = $this->period($request);
        $cap = (float) config('hrpro.payroll.social_security.wage_cap', 15000);

        $headers = ['ลำดับที่', 'เลขประจำตัวประชาชน', 'ชื่อ', 'ชื่อสกุล', 'ค่าจ้าง', 'จำนวนเงินสมทบ'];

        AuditLogger::log('exported', "ส่งออกรายละเอียดเงินสมทบ สปส.1-10 งวด {$month}/{$year}");

        return $this->streamCsv(sprintf('SSO-1-10-%d-%02d.csv', $year + 543, $month), $headers, function ($out) use ($year, $month, $cap) {
            $i = 0;
            $wages = 0.0;
            $total = 0.0;
            $this->periodRows($year, $month, function (Payroll $p) use ($out, $cap, &$i, &$wages, &$total) {
                if ((float) $p->social_security <= 0) {
                    return;
                }
                $wage = $cap > 0 ? min($this->wage($p), $cap) : $this->wage($p);
                $wages += $wage;
                $total += (float) $p->social_security;
                fputcsv($out, [
                    ++$i,
                    $this->digits($p->employee?->national_id),
                    $p->employee?->first_name ?? '',
                    $p->employee?->last_name ?? '',
                    $this->amount($wage),
                    $this->amount($p->social_security),
                ]);
            });
            fputcsv($out, ['', '', '', 'รวมค่าจ้าง / เงินสมทบผู้ประกันตน', $this->amount($wages), $this->amount($total)]);
            fputcsv($out, ['', '', '', 'เงินสมทบนายจ้าง', '', $this->amount($total)]);
            fputcsv($out, ['', '', '', 'รวมเงินสมทบทั้งสิ้น', '', $this->amount($total * 2)]);
        });
    }

    /** @return array{0: int, 1: int} */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return [(int) ($data['year'] ?? now()->year), (int) ($data['month'] ?? now()->month)];
    }

    /** Payroll rows of one period in name order, one at a time. */
    private function periodRows(int $year, int $month, callable $each): void
    {
        Payroll::query()
            ->forPeriod($year, $month)
            ->with('employee:id,first_name,last_name,national_id')
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.first_name')
            ->orderBy('employees.last_name')
            ->select('payrolls.*')
            ->get()   // one period is at most a few hundred rows
            ->each($each);
    }

    /** Pay actually earned in the period: the income for tax and the SSO wage. */
    private function wage(Payroll $p): float
    {
        return max(0, (float) $p->base_salary + (float) $p->allowances + (float) $p->overtime_pay - (float) $p->unpaid_leave_deduction);
    }

    private function amount(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /** A national / tax id as the thirteen digits the forms expect. */
    private function digits(?string $id): string
    {
        return preg_replace('/\D/', '', (string) $id);
    }

    /**
     * Stream a CSV download with a UTF-8 BOM (so Excel renders Thai correctly).
     */
    private function streamCsv(string $filename, array $headerRow, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headerRow, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, $headerRow);
            $rows($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
