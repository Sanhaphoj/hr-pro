<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\AuditLogger;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PayrollController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:payroll.view', only: ['index', 'show', 'printSlip', 'printRegister']),
            new Middleware('permission:payroll.manage', only: ['generate', 'finalize']),
        ];
    }

    public function index(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $payrolls = Payroll::query()
            ->forPeriod($year, $month)
            ->with('employee:id,first_name,last_name,email,employee_code')
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.first_name')
            ->select('payrolls.*')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        $totals = Payroll::forPeriod($year, $month)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(net_pay),0) as net, COALESCE(SUM(deductions),0) as ded')
            ->first();

        return view('payroll.index', [
            'payrolls' => $payrolls,
            'year' => $year,
            'month' => $month,
            'totals' => $totals,
        ]);
    }

    public function generate(Request $request, PayrollService $service): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $created = $service->generateForPeriod((int) $data['year'], (int) $data['month']);

        AuditLogger::log('created', "คำนวณเงินเดือนงวด {$data['month']}/{$data['year']} ({$created} รายการ)");

        return redirect()
            ->route('payroll.index', ['year' => $data['year'], 'month' => $data['month']])
            ->with('success', $created > 0 ? "สร้างรายการเงินเดือน {$created} รายการเรียบร้อย" : 'มีรายการเงินเดือนของงวดนี้อยู่แล้ว');
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load('employee.department', 'employee.position');

        return view('payroll.show', ['payroll' => $payroll]);
    }

    /**
     * The payslip as an A4 document, with the employee's year-to-date totals.
     */
    public function printSlip(Payroll $payroll): View
    {
        $payroll->load('employee.department', 'employee.position');

        $ytd = Payroll::query()
            ->where('employee_id', $payroll->employee_id)
            ->where('period_year', $payroll->period_year)
            ->where('period_month', '<=', $payroll->period_month)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(base_salary + allowances), 0) as earnings, COALESCE(SUM(deductions), 0) as ded, COALESCE(SUM(net_pay), 0) as net')
            ->first();

        AuditLogger::log('printed', "พิมพ์สลิปเงินเดือน #{$payroll->id}", $payroll);

        return view('print.payslip', [
            'payroll' => $payroll,
            'ytd' => [
                'count' => (int) $ytd->cnt,
                'earnings' => (float) $ytd->earnings,
                'deductions' => (float) $ytd->ded,
                'net' => (float) $ytd->net,
            ],
        ]);
    }

    /**
     * Every payslip of one period on a single register, with totals.
     */
    public function printRegister(Request $request): View
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($data['year'] ?? now()->year);
        $month = (int) ($data['month'] ?? now()->month);

        $payrolls = Payroll::query()
            ->forPeriod($year, $month)
            ->with('employee.department', 'employee.position')
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.first_name')
            ->orderBy('employees.last_name')
            ->select('payrolls.*')
            ->get();

        AuditLogger::log('printed', "พิมพ์รายงานเงินเดือนงวด {$month}/{$year}");

        return view('print.payroll-register', [
            'payrolls' => $payrolls,
            'year' => $year,
            'month' => $month,
            'totals' => [
                'base' => (float) $payrolls->sum('base_salary'),
                'allowances' => (float) $payrolls->sum('allowances'),
                'deductions' => (float) $payrolls->sum('deductions'),
                'net' => (float) $payrolls->sum('net_pay'),
                'finalized' => $payrolls->where('status', 'finalized')->count(),
            ],
        ]);
    }

    public function finalize(Payroll $payroll): RedirectResponse
    {
        $payroll->update(['status' => 'finalized']);

        AuditLogger::log('updated', "ยืนยันสลิปเงินเดือน #{$payroll->id}", $payroll);

        return back()->with('success', 'ยืนยันสลิปเงินเดือนเรียบร้อยแล้ว');
    }
}
