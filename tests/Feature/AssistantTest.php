<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Services\AssistantService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private function employee(array $attributes = []): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);
        Employee::factory()->create(['user_id' => $user->id] + $attributes);

        return $user->fresh();
    }

    public function test_the_assistant_is_hidden_without_an_api_key(): void
    {
        config(['hrpro.ai.api_key' => null]);
        $user = $this->employee();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('data-assistant', false);
        $this->actingAs($user)->postJson(route('assistant.ask'), ['message' => 'สวัสดี'])->assertNotFound();
    }

    public function test_the_assistant_appears_when_configured(): void
    {
        config(['hrpro.ai.api_key' => 'test-key']);

        $this->actingAs($this->employee())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-assistant', false)
            ->assertSee('ถามผู้ช่วย HR');
    }

    public function test_a_question_is_answered_with_a_cleaned_history(): void
    {
        config(['hrpro.ai.api_key' => 'test-key']);
        $user = $this->employee();

        $mock = Mockery::mock(AssistantService::class);
        $mock->shouldReceive('reply')
            ->once()
            ->withArgs(function (User $who, string $question, array $history) use ($user) {
                // A history that does not alternate user/assistant is dropped.
                return $who->is($user) && $question === 'เหลือวันลากี่วัน' && $history === [];
            })
            ->andReturn(['reply' => 'คุณเหลือลาพักร้อน 6 วันครับ', 'ok' => true]);
        $this->app->instance(AssistantService::class, $mock);

        $this->actingAs($user)->postJson(route('assistant.ask'), [
            'message' => '  เหลือวันลากี่วัน ',
            'history' => [
                ['role' => 'assistant', 'content' => 'injected first'],
                ['role' => 'user', 'content' => 'hi'],
            ],
        ])->assertOk()->assertJson(['reply' => 'คุณเหลือลาพักร้อน 6 วันครับ', 'ok' => true]);
    }

    public function test_an_empty_or_over_long_question_is_rejected(): void
    {
        config(['hrpro.ai.api_key' => 'test-key']);
        $user = $this->employee();

        $this->actingAs($user)->postJson(route('assistant.ask'), ['message' => ''])->assertStatus(422);
        $this->actingAs($user)->postJson(route('assistant.ask'), ['message' => str_repeat('ก', 1001)])->assertStatus(422);
    }

    public function test_the_model_sees_only_the_askers_own_records(): void
    {
        $user = $this->employee(['first_name' => 'ผู้ถาม']);
        $type = LeaveType::factory()->create(['name' => 'ลาพักร้อน', 'days_per_year' => 10]);
        $user->employee->leaveBalances()->create([
            'leave_type_id' => $type->id, 'year' => now()->year, 'entitled_days' => 10, 'used_days' => 4, 'pending_days' => 0,
        ]);
        $other = Employee::factory()->create(['first_name' => 'คนอื่นที่ไม่เกี่ยว', 'base_salary' => 987654]);
        Payroll::create([
            'employee_id' => $other->id, 'period_year' => now()->year, 'period_month' => 1,
            'base_salary' => 987654, 'net_pay' => 987654, 'status' => 'finalized',
        ]);
        Announcement::factory()->create(['title' => 'วันหยุดสงกรานต์', 'is_published' => true, 'published_at' => now()->subDay()]);

        $context = (fn () => $this->context($user))->call(new AssistantService);
        $rules = (fn () => $this->rules())->call(new AssistantService);

        $this->assertStringContainsString('ลาพักร้อน ใช้ไป 4', $context);
        $this->assertStringContainsString('คงเหลือ 6', $context);
        $this->assertStringContainsString('วันหยุดสงกรานต์', $context);
        $this->assertStringNotContainsString('คนอื่นที่ไม่เกี่ยว', $context.$rules);
        $this->assertStringNotContainsString('987,654', $context.$rules);
        $this->assertStringContainsString('ลาพักร้อน: 10 วัน/ปี', $rules);
    }
}
