<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveService();
    }

    private function makeEmployee(): Employee
    {
        return Employee::factory()->create([
            'department_id' => Department::factory()->create()->id,
        ]);
    }

    public function test_working_days_excludes_weekends(): void
    {
        // Mon 2 Jun 2025 -> Sun 8 Jun 2025 is a full week = 5 working days.
        $days = $this->service->workingDaysBetween(
            Carbon::parse('2025-06-02'),
            Carbon::parse('2025-06-08'),
        );

        $this->assertSame(5.0, $days);
    }

    public function test_working_days_is_zero_for_a_weekend_only_range(): void
    {
        // Sat 7 Jun 2025 -> Sun 8 Jun 2025.
        $days = $this->service->workingDaysBetween(
            Carbon::parse('2025-06-07'),
            Carbon::parse('2025-06-08'),
        );

        $this->assertSame(0.0, $days);
    }

    public function test_create_request_reserves_pending_balance(): void
    {
        $employee = $this->makeEmployee();
        $type = LeaveType::factory()->create(['days_per_year' => 10, 'requires_approval' => true]);

        $request = $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-02', // Mon
            'end_date' => '2025-06-04',   // Wed -> 3 working days
            'reason' => 'พักผ่อน',
        ]);

        $this->assertSame(LeaveRequest::STATUS_PENDING, $request->status);
        $this->assertEquals(3.0, (float) $request->total_days);

        $balance = $this->service->ensureBalance($employee, $type, 2025);
        $this->assertEquals(3.0, (float) $balance->pending_days);
        $this->assertEquals(0.0, (float) $balance->used_days);
        $this->assertEquals(7.0, $balance->remaining_days);
    }

    public function test_approve_moves_pending_days_to_used(): void
    {
        $employee = $this->makeEmployee();
        $approver = User::factory()->create();
        $type = LeaveType::factory()->create(['days_per_year' => 10, 'requires_approval' => true]);

        $request = $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-02',
            'end_date' => '2025-06-04',
            'reason' => 'พักผ่อน',
        ]);

        $this->service->approve($request->fresh(), $approver);

        $balance = $this->service->ensureBalance($employee, $type, 2025);
        $this->assertEquals(0.0, (float) $balance->pending_days);
        $this->assertEquals(3.0, (float) $balance->used_days);
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $request->fresh()->status);
    }

    public function test_reject_releases_pending_days(): void
    {
        $employee = $this->makeEmployee();
        $approver = User::factory()->create();
        $type = LeaveType::factory()->create(['days_per_year' => 10, 'requires_approval' => true]);

        $request = $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-02',
            'end_date' => '2025-06-04',
            'reason' => 'พักผ่อน',
        ]);

        $this->service->reject($request->fresh(), $approver, 'ช่วงเวลางานเร่งด่วน');

        $balance = $this->service->ensureBalance($employee, $type, 2025);
        $this->assertEquals(0.0, (float) $balance->pending_days);
        $this->assertEquals(0.0, (float) $balance->used_days);
        $this->assertSame(LeaveRequest::STATUS_REJECTED, $request->fresh()->status);
    }

    public function test_overlapping_request_is_rejected(): void
    {
        $employee = $this->makeEmployee();
        $type = LeaveType::factory()->create(['days_per_year' => 10]);

        $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-02',
            'end_date' => '2025-06-04',
            'reason' => 'ครั้งที่หนึ่ง',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-03',
            'end_date' => '2025-06-05',
            'reason' => 'ทับซ้อน',
        ]);
    }

    public function test_request_exceeding_balance_is_rejected(): void
    {
        $employee = $this->makeEmployee();
        $type = LeaveType::factory()->create(['days_per_year' => 2, 'requires_approval' => true]);

        $this->expectException(ValidationException::class);

        $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-02', // Mon
            'end_date' => '2025-06-06',   // Fri -> 5 working days > 2 entitled
            'reason' => 'เกินสิทธิ์',
        ]);
    }

    public function test_non_approval_type_is_auto_approved(): void
    {
        $employee = $this->makeEmployee();
        $type = LeaveType::factory()->create(['days_per_year' => 10, 'requires_approval' => false]);

        $request = $this->service->createRequest($employee, [
            'leave_type_id' => $type->id,
            'start_date' => '2025-06-02',
            'end_date' => '2025-06-02', // 1 day
            'reason' => 'ลากิจ',
        ]);

        $this->assertSame(LeaveRequest::STATUS_APPROVED, $request->status);
        $balance = $this->service->ensureBalance($employee, $type, 2025);
        $this->assertEquals(1.0, (float) $balance->used_days);
        $this->assertEquals(0.0, (float) $balance->pending_days);
    }
}
