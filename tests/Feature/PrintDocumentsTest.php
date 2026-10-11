<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PerformanceReview;
use App\Models\Role;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A4 documents printed, or saved as PDF, from the browser.
 */
class PrintDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $roleSlug, bool $withProfile = true): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', $roleSlug)->value('id')]);

        if ($withProfile) {
            Employee::factory()->create(['user_id' => $user->id]);
        }

        return $user->fresh();
    }

    private function payroll(Employee $employee, int $month, array $overrides = []): Payroll
    {
        return Payroll::create($overrides + [
            'employee_id' => $employee->id,
            'period_year' => 2026,
            'period_month' => $month,
            'base_salary' => 30000,
            'allowances' => 1500,
            'deductions' => 750.5,
            'net_pay' => 30749.5,
            'status' => 'finalized',
        ]);
    }

    // ------------------------------------------------------------ formatting --

    public function test_amounts_are_written_out_the_way_bahttext_does(): void
    {
        $this->assertSame('ศูนย์บาทถ้วน', thai_baht_text(0));
        $this->assertSame('หนึ่งบาทถ้วน', thai_baht_text(1));
        $this->assertSame('สิบบาทถ้วน', thai_baht_text(10));
        $this->assertSame('สิบเอ็ดบาทถ้วน', thai_baht_text(11));
        $this->assertSame('ยี่สิบเอ็ดบาทถ้วน', thai_baht_text(21));
        $this->assertSame('หนึ่งร้อยเอ็ดบาทถ้วน', thai_baht_text(101));
        $this->assertSame('สามหมื่นบาทถ้วน', thai_baht_text(30000));
        $this->assertSame('หนึ่งล้านเอ็ดบาทถ้วน', thai_baht_text(1000001));
        $this->assertSame('สิบเอ็ดล้านบาทถ้วน', thai_baht_text(11000000));
        $this->assertSame('สามหมื่นเจ็ดร้อยสี่สิบเก้าบาทห้าสิบสตางค์', thai_baht_text(30749.5));
        $this->assertSame('ห้าสิบสตางค์', thai_baht_text(0.5));
    }

    public function test_dates_use_thai_months_and_the_buddhist_era(): void
    {
        $this->assertSame('13 กันยายน 2569', thai_date('2026-09-13'));
        $this->assertSame('13 ก.ย. 69', thai_date('2026-09-13', 'short'));
        $this->assertSame('13/09/2569', thai_date('2026-09-13', 'numeric'));
        $this->assertSame('13 กันยายน 2569 เวลา 08:05 น.', thai_date('2026-09-13 08:05', 'long', true));
        $this->assertSame('—', thai_date(null));
        $this->assertSame('3 ปี 2 เดือน', thai_duration(Carbon::parse('2023-07-01'), Carbon::parse('2026-09-13')));
        $this->assertSame('PAY-256909-000123', doc_no('PAY', 123, '2026-09-01'));
    }

    public function test_national_ids_are_grouped_and_can_be_masked(): void
    {
        $this->assertSame('1-2345-67890-12-3', format_national_id('1234567890123'));
        $this->assertSame('1-xxxx-xxxx0-12-3', format_national_id('1234567890123', masked: true));
        $this->assertSame('—', format_national_id(null));
    }

    // --------------------------------------------------------------- payroll --

    public function test_a_payslip_prints_for_payroll_viewers_only(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'สมชาย', 'last_name' => 'ใจดี', 'national_id' => '1234567890123']);
        $this->payroll($employee, 8);
        $slip = $this->payroll($employee, 9);

        $this->actingAs($this->user('super-admin', false))
            ->get(route('payroll.print', $slip))
            ->assertOk()
            ->assertSee('ใบแจ้งเงินเดือน')
            ->assertSee('สมชาย ใจดี')
            ->assertSee('PAY-256909-')
            ->assertSee('สามหมื่นเจ็ดร้อยสี่สิบเก้าบาทห้าสิบสตางค์')
            // Year to date covers August and September.
            ->assertSee('61,499.00')
            // Only the last digits of the ID reach a payslip.
            ->assertSee('1-xxxx-xxxx0-12-3')
            ->assertDontSee('1-2345-67890-12-3')
            ->assertDontSee('ฉบับร่าง');

        $this->assertDatabaseHas('audit_logs', ['action' => 'printed', 'auditable_id' => $slip->id]);

        $this->actingAs($this->user('employee'))->get(route('payroll.print', $slip))->assertForbidden();
    }

    public function test_a_draft_payslip_says_so(): void
    {
        $slip = $this->payroll(Employee::factory()->create(), 9, ['status' => 'draft']);

        $this->actingAs($this->user('super-admin', false))
            ->get(route('payroll.print', $slip))
            ->assertOk()
            ->assertSee('ฉบับร่าง');
    }

    public function test_the_register_lists_one_period_and_is_not_read_as_a_payslip_id(): void
    {
        $first = Employee::factory()->create(['first_name' => 'อรุณ', 'last_name' => 'สว่าง']);
        $second = Employee::factory()->create(['first_name' => 'จันทร์', 'last_name' => 'เพ็ญ']);
        $other = Employee::factory()->create(['first_name' => 'งวด', 'last_name' => 'ก่อนหน้า']);
        $this->payroll($first, 9);
        $this->payroll($second, 9);
        $this->payroll($other, 8);

        $this->actingAs($this->user('super-admin', false))
            ->get('/payroll/print?year=2026&month=9')
            ->assertOk()
            ->assertSee('รายงานสรุปเงินเดือน')
            ->assertSee('อรุณ สว่าง')
            ->assertSee('จันทร์ เพ็ญ')
            ->assertDontSee('งวด ก่อนหน้า')
            ->assertSee('61,499.00');
    }

    // ----------------------------------------------------------------- leave --

    public function test_a_leave_form_prints_for_its_owner_and_hr_but_not_a_colleague(): void
    {
        $owner = $this->user('employee');
        $type = LeaveType::factory()->create(['name' => 'ลาป่วย', 'days_per_year' => 30]);
        $request = LeaveRequest::create([
            'employee_id' => $owner->employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-15',
            'total_days' => 2,
            'reason' => 'มีไข้สูง',
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('leave-requests.print', $request))
            ->assertOk()
            ->assertSee('ใบขออนุญาตลา')
            ->assertSee('ขอลาป่วย')
            ->assertSee('มีไข้สูง')
            ->assertSee('14 กันยายน 2569');

        $this->actingAs($this->user('employee'))->get(route('leave-requests.print', $request))->assertForbidden();
        $this->actingAs($this->user('hr-manager'))->get(route('leave-requests.print', $request))->assertOk();
    }

    // ------------------------------------------------------------- employees --

    public function test_the_employee_record_and_certificate_print_for_employee_viewers(): void
    {
        $admin = $this->user('super-admin', false);
        $employee = Employee::factory()->create([
            'base_salary' => 45000,
            'status' => 'active',
            'hire_date' => '2023-07-01',
            'national_id' => '1234567890123',
        ]);

        $this->actingAs($admin)->get(route('employees.print', $employee))
            ->assertOk()
            ->assertSee('ทะเบียนประวัติพนักงาน')
            ->assertSee('1-2345-67890-12-3');

        $this->actingAs($admin)->get(route('employees.certificate', $employee))
            ->assertOk()
            ->assertSee('หนังสือรับรองการทำงาน')
            ->assertSee('จนถึงปัจจุบัน')
            ->assertDontSee('45,000.00');

        $this->actingAs($admin)->get(route('employees.certificate', [
            $employee,
            'salary' => 1,
            'purpose' => 'เพื่อประกอบการขอสินเชื่อ<script>',
        ]))
            ->assertOk()
            ->assertSee('หนังสือรับรองการทำงานและเงินเดือน')
            ->assertSee('45,000.00')
            ->assertSee('สี่หมื่นห้าพันบาทถ้วน')
            ->assertSee('เพื่อประกอบการขอสินเชื่อ')
            ->assertDontSee('เพื่อเพื่อ')
            // The typed purpose is escaped, not run.
            ->assertDontSee('ขอสินเชื่อ<script>', false)
            ->assertSee('ขอสินเชื่อ&lt;script&gt;', false);

        $employeeUser = $this->user('employee');
        $this->actingAs($employeeUser)->get(route('employees.print', $employee))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('employees.certificate', $employee))->assertForbidden();
    }

    public function test_the_employee_register_follows_the_list_filters(): void
    {
        Employee::factory()->create(['first_name' => 'ทดสอบ', 'last_name' => 'ทำงานอยู่', 'status' => 'active']);
        Employee::factory()->create(['first_name' => 'ทดสอบ', 'last_name' => 'ลาออกแล้ว', 'status' => 'terminated']);

        $this->actingAs($this->user('super-admin', false))
            ->get('/employees/print?status=terminated')
            ->assertOk()
            ->assertSee('ทะเบียนรายชื่อพนักงาน')
            ->assertSee('ทดสอบ ลาออกแล้ว')
            ->assertDontSee('ทดสอบ ทำงานอยู่')
            ->assertSee('สถานะ พ้นสภาพ');
    }

    // ------------------------------------------------------------ attendance --

    public function test_a_timesheet_is_your_own_unless_you_may_see_everyone(): void
    {
        Carbon::setTestNow('2026-09-20 10:00:00');

        $owner = $this->user('employee');
        $colleague = $this->user('employee');
        Attendance::create([
            'employee_id' => $owner->employee->id,
            'work_date' => '2026-09-14',
            'clock_in' => '2026-09-14 09:05:00',
            'clock_out' => '2026-09-14 18:00:00',
            'worked_minutes' => 535,
            'status' => 'present',
        ]);

        $this->actingAs($owner)->get(route('attendance.print', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('ใบลงเวลาทำงาน')
            ->assertSee($owner->employee->full_name)
            ->assertSee('09:05')
            ->assertSee('8:55')
            ->assertSee('ไม่มีบันทึก');

        $this->actingAs($owner)
            ->get(route('attendance.print', ['employee_id' => $colleague->employee->id]))
            ->assertForbidden();

        // Asking an employee's timesheet for a day's register just gives their own.
        $this->actingAs($owner)->get(route('attendance.print', ['date' => '2026-09-14']))
            ->assertOk()
            ->assertSee('ใบลงเวลาทำงาน')
            ->assertDontSee('รายงานการลงเวลาประจำวัน');

        $this->actingAs($this->user('hr-manager'))
            ->get(route('attendance.print', ['employee_id' => $owner->employee->id, 'month' => '2026-09']))
            ->assertOk()
            ->assertSee($owner->employee->full_name);
    }

    public function test_hr_can_print_the_day_register_for_everyone(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');

        $hr = $this->user('hr-manager', false);
        $present = Employee::factory()->create(['first_name' => 'มาตรง', 'last_name' => 'เวลา', 'status' => 'active']);
        Employee::factory()->create(['first_name' => 'ไม่ได้', 'last_name' => 'ลงเวลา', 'status' => 'active']);
        Attendance::create([
            'employee_id' => $present->id,
            'work_date' => '2026-09-14',
            'clock_in' => '2026-09-14 08:55:00',
            'status' => 'present',
        ]);

        $this->actingAs($hr)->get(route('attendance.print', ['date' => '2026-09-14']))
            ->assertOk()
            ->assertSee('รายงานการลงเวลาประจำวัน')
            ->assertSee('มาตรง เวลา')
            ->assertSee('08:55')
            ->assertSee('ไม่ได้ ลงเวลา')
            ->assertSee('ไม่มีบันทึก');

        // Without a profile of their own, HR lands on today's register.
        $this->actingAs($hr)->get(route('attendance.print'))
            ->assertOk()
            ->assertSee('รายงานการลงเวลาประจำวัน');
    }

    // ----------------------------------------------------------------- other --

    public function test_review_training_and_announcement_documents(): void
    {
        $admin = $this->user('super-admin', false);
        $employee = Employee::factory()->create();

        $review = PerformanceReview::create([
            'employee_id' => $employee->id,
            'reviewer_id' => $admin->id,
            'period' => '2026-H1',
            'score' => 4,
            'strengths' => 'ทำงานละเอียดรอบคอบ',
            'improvements' => 'การนำเสนอ',
            'status' => 'submitted',
            'reviewed_at' => now(),
        ]);
        $this->actingAs($admin)->get(route('performance.print', $review))
            ->assertOk()
            ->assertSee('แบบบันทึกผลการประเมิน')
            ->assertSee('ทำงานละเอียดรอบคอบ')
            ->assertSee('ดีมาก');

        $course = TrainingCourse::create(['title' => 'ความปลอดภัยในการทำงาน', 'hours' => 6, 'scheduled_date' => '2026-10-01', 'is_active' => true]);
        TrainingEnrollment::create(['training_course_id' => $course->id, 'employee_id' => $employee->id, 'status' => 'enrolled']);
        $this->actingAs($admin)->get(route('training.print', $course))
            ->assertOk()
            ->assertSee('ใบลงชื่อผู้เข้ารับการอบรม')
            ->assertSee($employee->full_name);

        $notice = Announcement::factory()->create([
            'title' => 'วันหยุดประจำปี',
            'body' => "ย่อหน้าแรก\n\nย่อหน้าที่สอง",
            'author_id' => $admin->id,
        ]);
        $this->actingAs($admin)->get(route('announcements.print', $notice))
            ->assertOk()
            ->assertSee('เรื่อง วันหยุดประจำปี')
            ->assertSee('ย่อหน้าที่สอง');

        $draft = Announcement::factory()->draft()->create(['author_id' => $admin->id]);
        $this->actingAs($this->user('employee'))->get(route('announcements.print', $draft))->assertForbidden();
    }

    public function test_screens_link_to_their_documents_and_print_with_a_header(): void
    {
        $admin = $this->user('super-admin', false);
        $employee = Employee::factory()->create();
        $slip = $this->payroll($employee, 9);

        $this->actingAs($admin)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee(route('employees.print', $employee), false)
            ->assertSee(route('employees.certificate', $employee), false)
            ->assertSee('class="print-head"', false);

        $this->actingAs($admin)->get(route('payroll.show', $slip))
            ->assertOk()
            ->assertSee(route('payroll.print', $slip), false);
    }
}
