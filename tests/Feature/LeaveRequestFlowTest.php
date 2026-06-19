<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * End-to-end happy path: employee logs in → submits a leave request →
     * HR approves it → the employee's leave balance is updated and a
     * notification is created.
     */
    public function test_employee_can_submit_leave_and_hr_can_approve(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $department = Department::factory()->create();
        $leaveType = LeaveType::factory()->create([
            'days_per_year' => 10,
            'requires_approval' => true,
        ]);

        // Employee account + linked profile
        $employeeUser = User::factory()->create();
        $employeeUser->roles()->sync([Role::where('slug', 'employee')->value('id')]);
        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'department_id' => $department->id,
        ]);

        // HR approver account
        $hrUser = User::factory()->create();
        $hrUser->roles()->sync([Role::where('slug', 'hr-manager')->value('id')]);

        // --- 1) Employee submits a 3-working-day leave request (Mon–Wed) ---
        $response = $this->actingAs($employeeUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-06-02',
            'end_date' => '2025-06-04',
            'reason' => 'พาครอบครัวไปต่างจังหวัด',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'status' => LeaveRequest::STATUS_PENDING,
            'total_days' => 3.0,
        ]);

        $leaveRequest = LeaveRequest::firstOrFail();
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'pending_days' => 3.0,
        ]);

        // --- 2) HR approves the request ---
        $approve = $this->actingAs($hrUser)->post(route('leave-approvals.approve', $leaveRequest));
        $approve->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequest::STATUS_APPROVED,
            'approver_id' => $hrUser->id,
        ]);
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 3.0,
            'pending_days' => 0.0,
        ]);

        // Employee receives an in-app notification
        $this->assertDatabaseHas('notifications', ['user_id' => $employeeUser->id]);
    }

    public function test_employee_without_permission_cannot_access_approval_queue(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);

        $this->actingAs($user)->get(route('leave-approvals.index'))->assertForbidden();
    }
}
