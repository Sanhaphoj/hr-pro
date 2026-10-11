<?php

namespace Tests\Unit;

use App\Services\PayrollService;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    private PayrollService $payroll;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payroll = new PayrollService;
        config([
            'hrpro.payroll.days_per_month' => 30,
            'hrpro.payroll.hours_per_day' => 8,
            'hrpro.payroll.overtime_rate' => 1.5,
            'hrpro.payroll.withholding_tax.enabled' => true,
            'hrpro.payroll.withholding_tax.personal_allowance' => 60000,
            'hrpro.payroll.social_security.enabled' => false,
            'hrpro.payroll.provident_fund.enabled' => false,
        ]);
    }

    public function test_progressive_tax_brackets(): void
    {
        $this->assertSame(0.0, $this->payroll->annualTax(150000));
        $this->assertSame(7500.0, $this->payroll->annualTax(300000));
        $this->assertSame(27500.0, $this->payroll->annualTax(500000));
        // 0 + 7,500 + 20,000 + 37,500 + 50,000 + 250,000 on the first 2M, then 30%.
        $this->assertSame(365000.0 + 300000.0, $this->payroll->annualTax(3000000));
    }

    public function test_withholding_on_thirty_thousand_a_month(): void
    {
        // 360,000 − 100,000 expenses − 60,000 personal = 200,000 → 2,500 a year.
        $this->assertSame(208.33, $this->payroll->withholdingTax(30000));
    }

    public function test_withholding_takes_social_security_off_the_taxable_income(): void
    {
        // 600,000 − 100,000 − 60,000 − 9,000 SSO = 431,000 → 7,500 + 13,100.
        $this->assertSame(1716.67, $this->payroll->withholdingTax(50000, 750));
    }

    public function test_low_pay_owes_no_tax(): void
    {
        $this->assertSame(0.0, $this->payroll->withholdingTax(15000));
    }

    public function test_switching_tax_off_withholds_nothing(): void
    {
        config(['hrpro.payroll.withholding_tax.enabled' => false]);

        $this->assertSame(0.0, $this->payroll->withholdingTax(200000));
    }

    public function test_overtime_is_one_and_a_half_times_the_hourly_wage(): void
    {
        // 30,000 ÷ 30 ÷ 8 = 125 an hour × 1.5 × 10 hours.
        $this->assertSame(1875.0, $this->payroll->overtimePay(30000, 10));
        $this->assertSame(0.0, $this->payroll->overtimePay(30000, 0));
    }

    public function test_a_full_slip_adds_up(): void
    {
        config([
            'hrpro.payroll.social_security.enabled' => true,
            'hrpro.payroll.social_security.rate' => 5,
            'hrpro.payroll.social_security.wage_cap' => 15000,
            'hrpro.payroll.provident_fund.enabled' => true,
            'hrpro.payroll.provident_fund.rate' => 3,
        ]);

        $slip = $this->payroll->compute(40000, unpaid: 0, allowances: 2000, overtimeHours: 4);

        $this->assertSame(1000.0, $slip['overtime_pay']);           // 40,000/30/8 × 1.5 × 4
        $this->assertSame(750.0, $slip['social_security']);         // capped at 15,000 × 5%
        $this->assertSame(1200.0, $slip['provident_fund']);         // 3% of base
        $this->assertGreaterThan(0, $slip['withholding_tax']);
        $this->assertSame(
            round(40000 + 2000 + 1000 - $slip['deductions'], 2),
            $slip['net_pay'],
        );
        $this->assertSame(
            round($slip['social_security'] + $slip['provident_fund'] + $slip['withholding_tax'], 2),
            $slip['deductions'],
        );
    }
}
