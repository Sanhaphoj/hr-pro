<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The attendance log is personal data: you see your own unless you are on the
 * administrator tier (super-admin, or HR who runs payroll from it).
 */
class AttendanceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const MINE = 'ลงเวลาของฉันเอง';

    private const SOMEONE_ELSE = 'ลงเวลาของเพื่อนร่วมงาน';

    /** @return array{0: User, 1: Employee} */
    private function actor(string $roleSlug, string $surname): array
    {
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', $roleSlug)->value('id')]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'last_name' => $surname]);

        return [$user->fresh(), $employee];
    }

    /** Seeds one punch for the actor and one for an unrelated colleague. */
    private function seedTwoPunches(Employee $mine): void
    {
        Attendance::create([
            'employee_id' => $mine->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'present',
            'notes' => self::MINE,
        ]);

        Attendance::create([
            'employee_id' => Employee::factory()->create(['last_name' => 'เพื่อนร่วมงาน'])->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'present',
            'notes' => self::SOMEONE_ELSE,
        ]);
    }

    /** @return array<string, array{0: string,1: bool}> */
    public static function roles(): array
    {
        return [
            'พนักงาน sees only their own' => ['employee', false],
            'หัวหน้างาน sees only their own' => ['manager', false],
            'ผู้จัดการฝ่ายบุคคล sees everyone' => ['hr-manager', true],
            'ผู้ดูแลระบบสูงสุด sees everyone' => ['super-admin', true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_who_sees_whose_attendance(string $roleSlug, bool $seesEveryone): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$user, $employee] = $this->actor($roleSlug, 'ผู้ทดสอบ');
        $this->seedTwoPunches($employee);

        $response = $this->actingAs($user)->get(route('attendance.index'))->assertOk();

        if ($seesEveryone) {
            // The employee column only exists on the everyone view.
            $response->assertSee('ประวัติการลงเวลาทั้งหมด');
            $response->assertSee($employee->full_name);
            $response->assertSee('เพื่อนร่วมงาน');
        } else {
            $response->assertSee('ประวัติการลงเวลาของฉัน');
            $response->assertDontSee('เพื่อนร่วมงาน');
        }
    }

    public function test_a_supervisor_no_longer_holds_the_view_all_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$manager] = $this->actor('manager', 'หัวหน้า');

        $this->assertFalse($manager->hasPermission('attendance.viewAll'));
        // Approving leave is still their job.
        $this->assertTrue($manager->hasPermission('leave-approvals.approve'));
    }

    public function test_the_supervisor_head_count_strip_is_hidden_on_the_time_clock(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$manager] = $this->actor('manager', 'หัวหน้า');
        [$hr] = $this->actor('hr-manager', 'ฝ่ายบุคคล');

        $this->actingAs($manager)->get(route('time-clock.show'))->assertOk()->assertDontSee('ยังไม่ลงเวลา');
        $this->actingAs($hr)->get(route('time-clock.show'))->assertOk()->assertSee('ยังไม่ลงเวลา');
    }
}
