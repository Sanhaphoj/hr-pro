<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Self-service payslips: every employee reads their own, without needing
 * the payroll permission. Only confirmed slips are shown — a draft can
 * still change and is not yet a record of payment.
 */
class MyPayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        $payslips = $employee
            ? Payroll::where('employee_id', $employee->id)
                ->where('status', 'finalized')
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->paginate(12)
            : null;

        return view('payroll.mine', [
            'employee' => $employee,
            'payslips' => $payslips,
            'ytd' => $payslips?->first()?->yearToDate(),
        ]);
    }

    public function print(Request $request, Payroll $payroll): View
    {
        abort_unless(
            $payroll->employee_id === $request->user()->employee?->id && $payroll->isFinalized(),
            404,
        );

        $payroll->load('employee.department', 'employee.position');
        AuditLogger::log('printed', "พนักงานเปิดสลิปเงินเดือนของตนเอง #{$payroll->id}", $payroll);

        return view('print.payslip', [
            'payroll' => $payroll,
            'ytd' => $payroll->yearToDate(),
            'back' => route('my-payslips.index'),
        ]);
    }
}
