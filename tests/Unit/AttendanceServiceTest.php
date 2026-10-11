<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolved from the container so the injected GeofenceService comes along.
        $this->service = app(AttendanceService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_clock_in_before_grace_is_present(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 08:30:00')); // before 09:15
        $employee = Employee::factory()->create();

        $attendance = $this->service->clockIn($employee);

        $this->assertSame('present', $attendance->status);
        $this->assertNotNull($attendance->clock_in);
    }

    public function test_clock_in_after_grace_is_late(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 09:30:00')); // after 09:15
        $employee = Employee::factory()->create();

        $attendance = $this->service->clockIn($employee);

        $this->assertSame('late', $attendance->status);
    }

    public function test_double_clock_in_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $employee = Employee::factory()->create();
        $this->service->clockIn($employee);

        $this->expectException(ValidationException::class);
        $this->service->clockIn($employee);
    }

    public function test_clock_out_computes_worked_minutes(): void
    {
        $employee = Employee::factory()->create();

        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $this->service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2025-06-02 17:00:00'));
        $attendance = $this->service->clockOut($employee);

        $this->assertSame(480, $attendance->worked_minutes); // 8 hours
        $this->assertEquals(8.0, $attendance->worked_hours);
    }

    public function test_clock_out_without_clock_in_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 17:00:00'));
        $employee = Employee::factory()->create();

        $this->expectException(ValidationException::class);
        $this->service->clockOut($employee);
    }

    public function test_daily_summary_counts_where_everyone_stands(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $employees = Employee::factory(4)->create(['status' => 'active']);

        // One on time, one late, one away on approved leave, one no-show.
        $this->service->clockIn($employees[0]);

        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00'));
        $this->service->clockIn($employees[1]);

        LeaveRequest::create([
            'employee_id' => $employees[2]->id,
            'leave_type_id' => LeaveType::factory()->create()->id,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-03',
            'total_days' => 3,
            'reason' => 'ลาพักร้อนประจำปี',
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $summary = $this->service->dailySummary(Carbon::parse('2025-06-02'));

        $this->assertSame(4, $summary['headcount']);
        $this->assertSame(1, $summary['on_time']);
        $this->assertSame(1, $summary['late']);
        $this->assertSame(1, $summary['on_leave']);
        $this->assertSame(1, $summary['not_yet']);
        $this->assertSame(0, $summary['outside']);
    }

    public function test_daily_summary_counts_punches_that_landed_outside_every_zone(): void
    {
        config(['hrpro.attendance.require_location' => false]);
        WorkLocation::factory()->create([
            'latitude' => 13.7248,
            'longitude' => 100.5340,
            'radius_meters' => 100,
        ]);

        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $employee = Employee::factory()->create(['status' => 'active']);

        // ~333 m north of the only zone.
        $this->service->clockIn($employee, ['latitude' => 13.7278, 'longitude' => 100.5340, 'accuracy' => 10]);

        $this->assertSame(1, $this->service->dailySummary()['outside']);
    }

    public function test_a_reason_is_kept_on_a_late_arrival(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00')); // after 09:15
        $employee = Employee::factory()->create();

        $attendance = $this->service->clockIn($employee, null, '  รถติดบนทางด่วน  ');

        $this->assertSame('late', $attendance->status);
        $this->assertSame('รถติดบนทางด่วน', $attendance->late_reason);
    }

    public function test_a_reason_is_discarded_when_the_arrival_is_punctual(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 08:30:00'));
        $employee = Employee::factory()->create();

        // The browser should not be able to pin an excuse on a normal day.
        $attendance = $this->service->clockIn($employee, null, 'รถติด');

        $this->assertSame('present', $attendance->status);
        $this->assertNull($attendance->late_reason);
    }

    public function test_a_blank_reason_is_stored_as_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00'));
        $employee = Employee::factory()->create();

        $this->assertNull($this->service->clockIn($employee, null, '   ')->late_reason);
    }

    public function test_leaving_before_the_end_of_day_is_flagged_with_its_reason(): void
    {
        $employee = Employee::factory()->create();

        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $this->service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2025-06-02 15:00:00')); // well before 17:45
        $attendance = $this->service->clockOut($employee, null, 'พาลูกไปหาหมอ');

        $this->assertTrue($attendance->left_early);
        $this->assertSame('พาลูกไปหาหมอ', $attendance->early_leave_reason);
    }

    public function test_leaving_inside_the_end_of_day_grace_is_not_early(): void
    {
        $employee = Employee::factory()->create();

        Carbon::setTestNow(Carbon::parse('2025-06-02 09:00:00'));
        $this->service->clockIn($employee);

        // 17:50 sits inside the 15-minute grace before the 18:00 finish.
        Carbon::setTestNow(Carbon::parse('2025-06-02 17:50:00'));
        $attendance = $this->service->clockOut($employee, null, 'ไม่ควรถูกเก็บ');

        $this->assertFalse($attendance->left_early);
        $this->assertNull($attendance->early_leave_reason);
    }

    public function test_a_long_reason_is_truncated_to_the_column_width(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-02 10:30:00'));
        $employee = Employee::factory()->create();

        $attendance = $this->service->clockIn($employee, null, str_repeat('ก', 400));

        $this->assertSame(250, mb_strlen($attendance->late_reason));
    }
}
