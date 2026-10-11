<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PerformanceReview;
use App\Models\TrainingCourse;
use App\Models\User;
use App\Services\PayrollService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The full demo dataset, printed: every A4 document opens for the HR
 * manager, and the figures on them hang together.
 */
class DemoPrintSweepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hrpro.demo' => true]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_no_leave_request_is_decided_before_it_was_written(): void
    {
        $out = LeaveRequest::whereNotNull('approved_at')->get()
            ->filter(fn (LeaveRequest $r) => $r->approved_at->lt($r->created_at));

        $this->assertCount(0, $out, 'decided before submitted: #'.$out->pluck('id')->join(', #'));
    }

    public function test_every_printable_document_opens(): void
    {
        $hr = User::where('email', 'hr@hrpro.local')->firstOrFail();
        $employee = Employee::whereNotNull('national_id')->firstOrFail();
        $payroll = Payroll::firstOrFail();

        $urls = [
            route('employees.print-list'),
            route('employees.print', $employee),
            route('employees.certificate', [$employee, 'salary' => 1, 'purpose' => 'ประกอบการขอสินเชื่อ']),
            route('attendance.print', ['employee_id' => $employee->id]),
            route('attendance.print', ['date' => now()->subWeekday()->toDateString()]),
            route('payroll.print-register', ['year' => $payroll->period_year, 'month' => $payroll->period_month]),
            route('payroll.print', $payroll),
            route('performance.print', PerformanceReview::firstOrFail()),
            route('training.print', TrainingCourse::firstOrFail()),
            route('announcements.print', Announcement::firstOrFail()),
        ];
        foreach (LeaveRequest::pluck('id') as $id) {
            $urls[] = route('leave-requests.print', $id);
        }

        foreach ($urls as $url) {
            $this->actingAs($hr)->get($url)->assertOk()->assertSee('พิมพ์ / บันทึก PDF');
        }
    }

    public function test_the_payroll_register_adds_up_to_net_pay_with_overtime(): void
    {
        $hr = User::where('email', 'hr@hrpro.local')->firstOrFail();
        $slip = Payroll::where('status', 'draft')->firstOrFail();
        app(PayrollService::class)->recalculate($slip, 10, 500);

        $html = $this->actingAs($hr)
            ->get(route('payroll.print-register', ['year' => $slip->period_year, 'month' => $slip->period_month]))
            ->assertOk()
            ->assertSee('ค่าล่วงเวลา')
            ->getContent();

        $rows = Payroll::forPeriod($slip->period_year, $slip->period_month)->get();
        $net = $rows->sum(fn (Payroll $p) => (float) $p->base_salary + (float) $p->overtime_pay + (float) $p->allowances - (float) $p->deductions);

        $this->assertEqualsWithDelta($rows->sum('net_pay'), $net, 0.01);
        $this->assertStringContainsString(number_format($rows->sum('net_pay'), 2), $html);
        $this->assertStringContainsString(number_format((float) $rows->sum('overtime_pay'), 2), $html);
    }
}
