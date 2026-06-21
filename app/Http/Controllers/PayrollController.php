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
            new Middleware('permission:payroll.view', only: ['index', 'show']),
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

    public function finalize(Payroll $payroll): RedirectResponse
    {
        $payroll->update(['status' => 'finalized']);

        AuditLogger::log('updated', "ยืนยันสลิปเงินเดือน #{$payroll->id}", $payroll);

        return back()->with('success', 'ยืนยันสลิปเงินเดือนเรียบร้อยแล้ว');
    }
}
