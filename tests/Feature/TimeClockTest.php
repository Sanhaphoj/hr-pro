<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimeClockTest extends TestCase
{
    use RefreshDatabase;

    /** Centre of the zone used throughout these tests. */
    private const ZONE_LAT = 13.7248;

    private const ZONE_LNG = 100.5340;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employeeUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);
        Employee::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super-admin')->value('id')]);

        return $admin;
    }

    private function zone(int $radius = 200): WorkLocation
    {
        return WorkLocation::factory()->create([
            'name' => 'สำนักงานใหญ่',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'radius_meters' => $radius,
        ]);
    }

    // ---------------------------------------------------------------- page --

    public function test_time_clock_page_renders_for_an_employee(): void
    {
        $this->zone();

        $this->actingAs($this->employeeUser())
            ->get(route('time-clock.show'))
            ->assertOk()
            ->assertSee('ลงเวลาเข้างาน')
            ->assertSee('สำนักงานใหญ่');
    }

    public function test_time_clock_page_warns_when_the_account_has_no_employee_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);

        $this->actingAs($user)
            ->get(route('time-clock.show'))
            ->assertOk()
            ->assertSee('ยังไม่มีโปรไฟล์พนักงาน');
    }

    // --------------------------------------------------------------- punches --

    public function test_clock_in_inside_the_zone_is_recorded_with_the_location(): void
    {
        $zone = $this->zone();
        $user = $this->employeeUser();

        // ~111 m north of the centre, inside the 200 m radius.
        $response = $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT + 0.001,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 12,
        ]);

        $response->assertRedirect(route('time-clock.show'))->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'clock_in_location_id' => $zone->id,
            'clock_in_accuracy' => 12,
        ]);
    }

    public function test_an_off_site_clock_in_without_a_reason_is_accepted(): void
    {
        $this->zone(100);
        $user = $this->employeeUser();

        // ~333 m north — well outside the 100 m radius, and no reason given.
        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT + 0.003,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
        ])->assertSessionHas('success');

        $row = Attendance::first();
        $this->assertNull($row->clock_in_location_id);
        $this->assertNull($row->clock_in_off_site_reason);
        // Flagged from the stored position, not from whether a reason exists.
        $this->assertTrue($row->clock_in_off_site);
    }

    public function test_a_whitespace_only_off_site_reason_counts_as_none(): void
    {
        $this->zone(100);
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT + 0.003,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
            'off_site_reason' => "   \n  ",
        ])->assertSessionHas('success');

        $this->assertNull(Attendance::first()->clock_in_off_site_reason);
    }

    public function test_an_off_site_clock_in_with_a_reason_is_accepted_and_flagged(): void
    {
        $this->zone(100);
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT + 0.003,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
            'off_site_reason' => 'ไปพบลูกค้าที่บริษัท ABC',
        ])->assertSessionHas('success');

        // Accepted, credited to no zone, and the reason is on record.
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'clock_in_location_id' => null,
            'clock_in_off_site_reason' => 'ไปพบลูกค้าที่บริษัท ABC',
        ]);
        $this->assertNotNull(Attendance::first()->clock_in_latitude);
    }

    public function test_an_off_site_reason_sent_from_inside_a_zone_is_dropped(): void
    {
        $zone = $this->zone();
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 9,
            'off_site_reason' => 'ไม่ควรถูกบันทึก',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'clock_in_location_id' => $zone->id,
            'clock_in_off_site_reason' => null,
        ]);
    }

    public function test_an_off_site_clock_out_keeps_its_own_reason(): void
    {
        $this->zone(100);
        $user = $this->employeeUser();

        // In inside the zone, out from ~333 m away.
        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in', 'latitude' => self::ZONE_LAT, 'longitude' => self::ZONE_LNG, 'accuracy' => 9,
        ])->assertSessionHas('success');

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'out',
            'latitude' => self::ZONE_LAT + 0.003,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 9,
            'off_site_reason' => 'ประชุมที่ไซต์งาน',
        ])->assertSessionHas('success');

        $row = Attendance::first();
        $this->assertFalse($row->clock_in_off_site);
        $this->assertNull($row->clock_in_off_site_reason);
        $this->assertTrue($row->clock_out_off_site);
        $this->assertSame('ประชุมที่ไซต์งาน', $row->clock_out_off_site_reason);
    }

    public function test_a_punch_before_any_zone_existed_is_not_counted_as_off_site(): void
    {
        // No zone at all: a position is recorded, but there was nothing to be
        // outside of.
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in', 'latitude' => self::ZONE_LAT, 'longitude' => self::ZONE_LNG, 'accuracy' => 9,
        ])->assertSessionHas('success');

        $this->assertFalse(Attendance::first()->clock_in_off_site);
    }

    public function test_a_late_off_site_punch_keeps_both_reasons_apart(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00')); // after the 09:15 grace
        $this->zone(100);
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT + 0.003,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
            'reason' => 'รถติด',
            'off_site_reason' => 'ไปพบลูกค้าก่อนเข้าออฟฟิศ',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'status' => 'late',
            'late_reason' => 'รถติด',
            'clock_in_off_site_reason' => 'ไปพบลูกค้าก่อนเข้าออฟฟิศ',
        ]);
    }

    public function test_clock_in_without_coordinates_is_rejected_while_zones_exist(): void
    {
        $this->zone();
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in']);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_clock_in_without_coordinates_is_allowed_when_no_zone_is_defined(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)
            ->post(route('time-clock.store'), ['action' => 'in'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'clock_in_location_id' => null,
        ]);
    }

    public function test_an_imprecise_fix_is_rejected(): void
    {
        config(['hrpro.attendance.max_accuracy_meters' => 50]);
        $this->zone();
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 400,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_an_out_of_zone_punch_is_accepted_when_enforcement_is_off(): void
    {
        config(['hrpro.attendance.require_location' => false]);
        $this->zone(100);
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT + 0.003,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
        ])->assertSessionHas('success');

        // The punch is kept for the record, but not credited to any zone.
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'clock_in_location_id' => null,
        ]);
    }

    public function test_clock_out_records_the_location_and_hours(): void
    {
        $zone = $this->zone();
        $user = $this->employeeUser();

        $payload = [
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 9,
        ];

        $this->actingAs($user)->post(route('time-clock.store'), $payload + ['action' => 'in']);
        $this->actingAs($user)->post(route('time-clock.store'), $payload + ['action' => 'out'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'clock_out_location_id' => $zone->id,
        ]);
    }

    public function test_a_second_clock_in_on_the_same_day_is_rejected(): void
    {
        $this->zone();
        $user = $this->employeeUser();

        $payload = [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 9,
        ];

        $this->actingAs($user)->post(route('time-clock.store'), $payload)->assertSessionHas('success');
        $this->actingAs($user)->post(route('time-clock.store'), $payload)->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_a_late_punch_records_the_reason_sent_with_it(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00')); // after the 09:15 grace
        $this->zone();
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
            'reason' => 'รถติดบนทางด่วน',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'status' => 'late',
            'late_reason' => 'รถติดบนทางด่วน',
        ]);
    }

    public function test_a_late_punch_without_a_reason_still_goes_through(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00'));
        $this->zone();
        $user = $this->employeeUser();

        // The prompt is advisory; declining to explain must not block the punch.
        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 10,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'status' => 'late',
            'late_reason' => null,
        ]);
    }

    public function test_an_early_clock_out_records_the_reason_and_the_flag(): void
    {
        $this->zone();
        $user = $this->employeeUser();
        $payload = ['latitude' => self::ZONE_LAT, 'longitude' => self::ZONE_LNG, 'accuracy' => 10];

        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $this->actingAs($user)->post(route('time-clock.store'), $payload + ['action' => 'in']);

        Carbon::setTestNow(Carbon::parse('2025-06-02 15:00:00')); // before the 17:45 threshold
        $this->actingAs($user)->post(route('time-clock.store'), $payload + [
            'action' => 'out',
            'reason' => 'พาลูกไปหาหมอ',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'left_early' => true,
            'early_leave_reason' => 'พาลูกไปหาหมอ',
        ]);
    }

    public function test_an_over_long_reason_is_rejected_by_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00'));
        $this->zone();

        $this->actingAs($this->employeeUser())->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'reason' => str_repeat('ก', 300),
        ])->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('attendances', 0);
    }

    // ------------------------------------------------------- administration --

    public function test_admin_can_create_a_work_location(): void
    {
        $response = $this->actingAs($this->admin())->post(route('work-locations.store'), [
            'name' => 'สาขาลาดพร้าว',
            'code' => 'BR-LP',
            'latitude' => 13.8161,
            'longitude' => 100.5601,
            'radius_meters' => 150,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('work-locations.index'));
        $this->assertDatabaseHas('work_locations', ['code' => 'BR-LP', 'radius_meters' => 150]);
    }

    public function test_a_radius_below_the_gps_error_floor_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('work-locations.store'), [
            'name' => 'เล็กเกินไป',
            'code' => 'TINY',
            'latitude' => 13.8161,
            'longitude' => 100.5601,
            'radius_meters' => 5,
        ])->assertSessionHasErrors('radius_meters');

        $this->assertDatabaseCount('work_locations', 0);
    }

    public function test_an_employee_cannot_manage_work_locations(): void
    {
        $this->actingAs($this->employeeUser())
            ->get(route('work-locations.index'))
            ->assertForbidden();
    }

    public function test_deleting_a_zone_keeps_the_attendance_history(): void
    {
        $zone = $this->zone();
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => self::ZONE_LAT,
            'longitude' => self::ZONE_LNG,
            'accuracy' => 9,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('work-locations.destroy', $zone))
            ->assertRedirect(route('work-locations.index'));

        // The row survives; only the link back to the deleted zone is cleared.
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee->id,
            'clock_in_location_id' => null,
        ]);
        $this->assertNotNull($user->employee->fresh()->attendances()->first()?->clock_in_latitude);
    }
}
