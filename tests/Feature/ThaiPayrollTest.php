<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThaiPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config([
            'hrpro.payroll.social_security.enabled' => true,
            'hrpro.payroll.withholding_tax.enabled' => true,
        ]);
    }

    private function userWithRole(string $slug, array $employee = []): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', $slug)->value('id')]);
        Employee::factory()->create(['user_id' => $user->id] + $employee);

        return $user->fresh();
    }

    public function test_generated_slips_carry_itemised_tax_and_social_security(): void
    {
        $hr = $this->userWithRole('hr-manager', ['base_salary' => 50000]);

        $this->actingAs($hr)->post(route('payroll.generate'), ['year' => 2026, 'month' => 10])->assertRedirect();

        $slip = Payroll::where('employee_id', $hr->employee->id)->firstOrFail();
        $this->assertEquals(750, $slip->social_security);
        $this->assertEquals(1716.67, $slip->withholding_tax);
        $this->assertEquals(50000 - 750 - 1716.67, $slip->net_pay);
    }

    public function test_hr_adds_overtime_to_a_draft_and_everything_is_recalculated(): void
    {
        $hr = $this->userWithRole('hr-manager', ['base_salary' => 30000]);
        $this->actingAs($hr)->post(route('payroll.generate'), ['year' => 2026, 'month' => 10]);
        $slip = Payroll::firstOrFail();
        $before = (float) $slip->withholding_tax;

        $this->actingAs($hr)
            ->put(route('payroll.update', $slip), ['overtime_hours' => 10, 'allowances' => 1000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $slip->refresh();
        $this->assertEquals(1875, $slip->overtime_pay);
        $this->assertEquals(1000, $slip->allowances);
        $this->assertGreaterThan($before, (float) $slip->withholding_tax);
        $this->assertEquals(round(30000 + 1000 + 1875 - (float) $slip->deductions, 2), (float) $slip->net_pay);
    }

    public function test_a_confirmed_slip_cannot_be_edited(): void
    {
        $hr = $this->userWithRole('hr-manager', ['base_salary' => 30000]);
        $this->actingAs($hr)->post(route('payroll.generate'), ['year' => 2026, 'month' => 10]);
        $slip = Payroll::firstOrFail();
        $slip->update(['status' => 'finalized']);

        $this->actingAs($hr)
            ->put(route('payroll.update', $slip), ['overtime_hours' => 10, 'allowances' => 0])
            ->assertForbidden();
    }

    public function test_pnd1_lists_income_and_tax_with_the_thirteen_digit_id(): void
    {
        $hr = $this->userWithRole('hr-manager', ['base_salary' => 50000, 'national_id' => '1-1012-34567-89-0', 'first_name' => 'ทดสอบ']);
        $this->actingAs($hr)->post(route('payroll.generate'), ['year' => 2026, 'month' => 10]);

        $csv = $this->actingAs($hr)->get(route('export.pnd1', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('1101234567890', $csv);
        $this->assertStringContainsString('ทดสอบ', $csv);
        $this->assertStringContainsString('50000.00', $csv);
        $this->assertStringContainsString('1716.67', $csv);
    }

    public function test_sso_export_doubles_the_contribution_for_the_employer(): void
    {
        $hr = $this->userWithRole('hr-manager', ['base_salary' => 50000]);
        $this->actingAs($hr)->post(route('payroll.generate'), ['year' => 2026, 'month' => 10]);

        $csv = $this->actingAs($hr)->get(route('export.sso', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('15000.00', $csv);   // wage capped
        $this->assertStringContainsString('750.00', $csv);
        $this->assertStringContainsString('1500.00', $csv);    // employee + employer
    }

    public function test_an_employee_cannot_export_payroll(): void
    {
        $user = $this->userWithRole('employee');

        $this->actingAs($user)->get(route('export.pnd1'))->assertForbidden();
    }

    public function test_an_employee_sees_only_their_own_confirmed_payslips(): void
    {
        $user = $this->userWithRole('employee', ['base_salary' => 30000]);
        $other = Employee::factory()->create(['base_salary' => 90000]);
        app(\App\Services\PayrollService::class)->generateForPeriod(2026, 9);
        app(\App\Services\PayrollService::class)->generateForPeriod(2026, 10);

        $mine = Payroll::where('employee_id', $user->employee->id)->where('period_month', 9)->firstOrFail();
        $mine->update(['status' => 'finalized']);
        $draft = Payroll::where('employee_id', $user->employee->id)->where('period_month', 10)->firstOrFail();
        $theirs = Payroll::where('employee_id', $other->id)->where('period_month', 9)->firstOrFail();
        $theirs->update(['status' => 'finalized']);

        $this->actingAs($user)->get(route('my-payslips.index'))
            ->assertOk()
            ->assertSee(route('my-payslips.print', $mine))
            ->assertDontSee(route('my-payslips.print', $draft))
            ->assertDontSee(route('my-payslips.print', $theirs));

        $this->actingAs($user)->get(route('my-payslips.print', $mine))->assertOk()->assertSee('ใบแจ้งเงินเดือน');
        $this->actingAs($user)->get(route('my-payslips.print', $draft))->assertNotFound();
        $this->actingAs($user)->get(route('my-payslips.print', $theirs))->assertNotFound();
        $this->actingAs($user)->get(route('payroll.show', $mine))->assertForbidden();
    }
}
