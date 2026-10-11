<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow('2026-10-14 10:00:00'); // a Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithRole(string $slug, array $employee = []): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', $slug)->value('id')]);
        Employee::factory()->create(['user_id' => $user->id, 'status' => 'active'] + $employee);

        return $user->fresh();
    }

    public function test_an_employee_sees_their_own_figures_not_the_organisations(): void
    {
        $user = $this->userWithRole('employee');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('วันลาคงเหลือ')
            ->assertSee('ชั่วโมงทำงานเดือนนี้')
            ->assertSee('วันนี้คุณยังไม่ได้ลงเวลาเข้างาน')
            ->assertDontSee('พนักงานทั้งหมด')
            ->assertDontSee('รออนุมัติการลา')
            ->assertDontSee('การลงเวลาเข้างาน');
    }

    public function test_hr_sees_organisation_figures_and_the_attendance_chart(): void
    {
        $user = $this->userWithRole('hr-manager');
        $other = Employee::factory()->create(['status' => 'active']);
        Attendance::create([
            'employee_id' => $other->id, 'work_date' => '2026-10-13',
            'clock_in' => '2026-10-13 09:40:00', 'status' => 'late',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('พนักงานทั้งหมด')
            ->assertSee('รออนุมัติการลา')
            ->assertSee('การลงเวลาเข้างาน')
            ->assertSee('class="viz__svg"', false)
            ->assertSee('มาสาย 1');
    }

    public function test_a_lead_sees_where_their_team_is_today(): void
    {
        $lead = $this->userWithRole('manager');
        $late = Employee::factory()->create(['manager_id' => $lead->employee->id, 'status' => 'active', 'first_name' => 'สมชาย']);
        Employee::factory()->create(['manager_id' => $lead->employee->id, 'status' => 'active', 'first_name' => 'สมหญิง']);
        Attendance::create([
            'employee_id' => $late->id, 'work_date' => '2026-10-14',
            'clock_in' => '2026-10-14 09:31:00', 'status' => 'late',
        ]);

        $this->actingAs($lead)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ทีมของฉันวันนี้')
            ->assertSee('มาทำงาน 1 จาก 2 คน')
            ->assertSeeInOrder(['สมชาย', 'มาสาย', '09:31', 'สมหญิง', 'ยังไม่ลงเวลา']);
    }

    public function test_birthdays_this_month_are_celebrated_without_the_year(): void
    {
        $user = $this->userWithRole('employee');
        Employee::factory()->create(['first_name' => 'วันเกิด', 'status' => 'active', 'date_of_birth' => '1990-10-14']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('โอกาสพิเศษเดือนตุลาคม')
            ->assertSee('วันเกิด')
            ->assertSee('วันนี้!')
            ->assertDontSee('1990');
    }

    public function test_leave_balances_show_what_is_left(): void
    {
        $user = $this->userWithRole('employee');
        $type = LeaveType::factory()->create(['name' => 'ลาพักร้อน', 'days_per_year' => 10]);
        $user->employee->leaveBalances()->create([
            'leave_type_id' => $type->id, 'year' => 2026,
            'entitled_days' => 10, 'used_days' => 3, 'pending_days' => 1,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ลาพักร้อน')
            ->assertSee('<b class="meter__value">6</b> / 10 วัน', false);
    }

    public function test_leave_calendar_hides_a_colleagues_leave_type(): void
    {
        $department = Department::factory()->create();
        $user = $this->userWithRole('employee', ['department_id' => $department->id]);
        $colleague = Employee::factory()->create(['department_id' => $department->id, 'first_name' => 'เพื่อนร่วมทีม']);
        $stranger = Employee::factory()->create(['first_name' => 'ต่างแผนก']);
        $sick = LeaveType::factory()->create(['name' => 'ลาป่วยพิเศษ']);

        foreach ([$colleague, $stranger] as $employee) {
            LeaveRequest::create([
                'employee_id' => $employee->id, 'leave_type_id' => $sick->id,
                'start_date' => '2026-10-15', 'end_date' => '2026-10-16', 'total_days' => 2, 'reason' => 'ธุระ',
                'status' => LeaveRequest::STATUS_APPROVED,
            ]);
        }

        $this->actingAs($user)->get(route('leave-calendar.index'))
            ->assertOk()
            ->assertSee('เพื่อนร่วมทีม')
            ->assertDontSee('ต่างแผนก')
            ->assertDontSee('ลาป่วยพิเศษ');
    }

    public function test_hr_sees_every_department_and_the_leave_type_on_the_calendar(): void
    {
        $user = $this->userWithRole('hr-manager');
        $someone = Employee::factory()->create(['first_name' => 'ต่างแผนก']);
        $type = LeaveType::factory()->create(['name' => 'ลากิจพิเศษ']);
        LeaveRequest::create([
            'employee_id' => $someone->id, 'leave_type_id' => $type->id,
            'start_date' => '2026-10-20', 'end_date' => '2026-10-20', 'total_days' => 1, 'reason' => 'ธุระ',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($user)->get(route('leave-calendar.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('ต่างแผนก')
            ->assertSee('ลากิจพิเศษ')
            ->assertSee('is-pending');
    }
}
