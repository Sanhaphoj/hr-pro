<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase2Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', 'super-admin')->value('id')]);

        return $user;
    }

    public function test_admin_can_view_all_phase2_indexes(): void
    {
        $admin = $this->admin();

        foreach (['payroll.index', 'recruitment.index', 'performance.index', 'training.index', 'documents.index', 'onboarding.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_payroll_generation_creates_rows(): void
    {
        $admin = $this->admin();
        Employee::factory()->create(['base_salary' => 30000]);

        $this->actingAs($admin)
            ->post(route('payroll.generate'), ['year' => 2026, 'month' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('payrolls', ['period_year' => 2026, 'period_month' => 5]);
    }

    public function test_performance_review_can_be_created(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->post(route('performance.store'), [
            'employee_id' => $employee->id,
            'period' => '2026-H1',
            'score' => 4,
            'strengths' => 'ดีมาก',
            'improvements' => 'พัฒนาต่อ',
        ])->assertRedirect();

        $this->assertDatabaseHas('performance_reviews', [
            'employee_id' => $employee->id,
            'score' => 4,
            'status' => 'submitted',
        ]);
    }

    public function test_document_upload_and_listing(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('documents.store'), [
            'title' => 'นโยบายบริษัท',
            'category' => 'policy',
            'file' => UploadedFile::fake()->create('policy.pdf', 80, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', ['title' => 'นโยบายบริษัท', 'category' => 'policy']);
        $doc = \App\Models\Document::first();
        Storage::disk('public')->assertExists($doc->file_path);
    }

    public function test_recruitment_posting_and_candidate_flow(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('recruitment.store'), [
            'title' => 'นักบัญชี',
            'openings' => 1,
            'employment_type' => 'full_time',
        ])->assertRedirect();

        $posting = \App\Models\JobPosting::first();
        $this->assertNotNull($posting);

        $this->actingAs($admin)->post(route('recruitment.candidates.store', $posting), [
            'name' => 'ผู้สมัคร ทดสอบ',
        ])->assertRedirect();

        $this->assertDatabaseHas('candidates', ['job_posting_id' => $posting->id, 'stage' => 'applied']);
    }

    public function test_onboarding_checklist_is_seeded_and_toggleable(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        // First visit seeds the checklist from the template.
        $this->actingAs($user)->get(route('onboarding.index'))->assertOk();
        $this->assertDatabaseCount('onboarding_tasks', count(\App\Models\OnboardingTask::TEMPLATE));

        $task = $employee->onboardingTasks()->first();
        $this->actingAs($user)->post(route('onboarding.toggle', $task))->assertRedirect();
        $this->assertDatabaseHas('onboarding_tasks', ['id' => $task->id, 'is_done' => true]);
    }

    public function test_locale_switch_is_remembered(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('locale.update', 'en'))
            ->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_employee_role_is_forbidden_on_payroll(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);

        $this->actingAs($user)->get(route('payroll.index'))->assertForbidden();
    }
}
