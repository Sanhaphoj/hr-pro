<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('org-chart.index'))->assertRedirect(route('login'));
    }

    public function test_any_active_user_can_view_org_chart(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Department::factory()->create(['name' => 'ทรัพยากรบุคคล', 'code' => 'DPT-HR']);

        $this->actingAs($user)
            ->get(route('org-chart.index'))
            ->assertOk()
            ->assertSee('ผังองค์กร')
            ->assertSee('ทรัพยากรบุคคล')
            ->assertSee(config('hrpro.company_name'));
    }

    public function test_chart_shows_department_head_and_headcount(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $department = Department::factory()->create(['name' => 'เทคโนโลยีสารสนเทศ']);
        $head = Employee::factory()->create([
            'first_name' => 'วิชัย',
            'last_name' => 'หัวหน้าไอที',
            'department_id' => $department->id,
        ]);
        $department->update(['manager_id' => $head->id]);
        Employee::factory()->count(3)->create(['department_id' => $department->id]);

        $this->actingAs($user)
            ->get(route('org-chart.index'))
            ->assertOk()
            ->assertSee('วิชัย หัวหน้าไอที')   // head shown
            ->assertSee('4 คน');               // 1 head + 3 = 4 employees in dept
    }
}
