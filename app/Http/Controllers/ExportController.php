<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:employees.view', only: ['employees']),
            new Middleware('permission:payroll.view', only: ['payroll']),
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

        $headers = ['รหัสพนักงาน', 'ชื่อ-นามสกุล', 'งวด', 'เงินเดือนฐาน', 'เบี้ยเลี้ยง', 'หัก', 'สุทธิ', 'สถานะ'];

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
                            $p->deductions,
                            $p->net_pay,
                            $p->status,
                        ]);
                    }
                });
        });
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
