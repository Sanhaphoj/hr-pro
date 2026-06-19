<?php

namespace Tests\Unit;

use App\Models\Employee;
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
        $this->service = new AttendanceService();
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
}
