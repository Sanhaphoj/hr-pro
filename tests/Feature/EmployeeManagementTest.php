<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super-admin')->value('id')]);

        return $admin;
    }

    public function test_admin_can_view_employee_list(): void
    {
        $this->actingAs($this->admin())->get(route('employees.index'))->assertOk();
    }

    public function test_admin_can_create_an_employee(): void
    {
        $admin = $this->admin();
        $department = Department::factory()->create();

        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'first_name' => 'อาทิตย์',
            'last_name' => 'ทดสอบ',
            'email' => 'arthit@hrpro.local',
            'department_id' => $department->id,
            'employment_type' => 'full_time',
            'status' => 'active',
            'hire_date' => '2024-01-15',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'email' => 'arthit@hrpro.local',
            'first_name' => 'อาทิตย์',
        ]);
    }

    public function test_admin_can_delete_an_employee(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->delete(route('employees.destroy', $employee))->assertRedirect();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_employee_without_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);

        $this->actingAs($user)->get(route('employees.index'))->assertForbidden();
    }
}
