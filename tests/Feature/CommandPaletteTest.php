<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandPaletteTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', $slug)->value('id')]);

        return $user;
    }

    public function test_hr_can_find_an_employee_by_name(): void
    {
        $user = $this->userWithRole('hr-manager');
        $employee = Employee::factory()->create(['first_name' => 'ประยุทธ', 'last_name' => 'ค้นเจอ']);

        $this->actingAs($user)->getJson(route('search', ['q' => 'ประยุ']))
            ->assertOk()
            ->assertJsonPath('results.0.title', 'ประยุทธ ค้นเจอ')
            ->assertJsonPath('results.0.url', route('employees.show', $employee));
    }

    public function test_an_employee_cannot_search_the_staff_register(): void
    {
        $user = $this->userWithRole('employee');
        Employee::factory()->create(['first_name' => 'ประยุทธ']);
        Announcement::factory()->create(['title' => 'ประยุกต์ใช้ระบบใหม่', 'is_published' => true, 'published_at' => now()->subDay()]);

        $this->actingAs($user)->getJson(route('search', ['q' => 'ประยุ']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.group', 'ประกาศ');
    }

    public function test_a_one_letter_query_returns_nothing(): void
    {
        $user = $this->userWithRole('hr-manager');
        Employee::factory()->create();

        $this->actingAs($user)->getJson(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_the_palette_offers_only_pages_the_person_can_open(): void
    {
        $user = $this->userWithRole('employee');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="cmdk"', false)
            ->assertSee(json_encode(route('leave-calendar.index')), false)
            ->assertDontSee(json_encode(route('payroll.index')), false)
            ->assertDontSee(json_encode(route('settings.users.index')), false);
    }

    public function test_the_top_bar_switch_remembers_the_scheme(): void
    {
        $user = $this->userWithRole('employee');

        $this->actingAs($user)->patchJson(route('settings.appearance.scheme'), ['color_scheme' => 'dark'])
            ->assertOk();
        $this->assertSame('dark', $user->fresh()->color_scheme);

        $this->actingAs($user)->patchJson(route('settings.appearance.scheme'), ['color_scheme' => 'neon'])
            ->assertStatus(422);
    }
}
