<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\PdfRenderer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    private string $fakeChrome;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        // Any existing file passes for "Chrome is installed" when rendering is mocked.
        $this->fakeChrome = base_path('artisan');
        config(['hrpro.pdf.chrome_path' => $this->fakeChrome, 'hrpro.payroll.withholding_tax.enabled' => false]);
    }

    private function userWithRole(string $slug, array $employee = []): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', $slug)->value('id')]);
        Employee::factory()->create(['user_id' => $user->id, 'base_salary' => 30000] + $employee);

        return $user->fresh();
    }

    private function slipFor(User $user): Payroll
    {
        app(PayrollService::class)->generateForPeriod(2026, 5);

        return Payroll::where('employee_id', $user->employee->id)->firstOrFail();
    }

    public function test_the_download_button_appears_when_chrome_is_available(): void
    {
        $hr = $this->userWithRole('hr-manager');
        $slip = $this->slipFor($hr);

        $this->actingAs($hr)->get(route('payroll.print', $slip))
            ->assertOk()
            ->assertSee('ดาวน์โหลด PDF')
            ->assertSee('download=pdf', false);
    }

    public function test_without_chrome_there_is_no_button_and_no_download(): void
    {
        config(['hrpro.pdf.chrome_path' => base_path('no-such-chrome')]);
        $hr = $this->userWithRole('hr-manager');
        $slip = $this->slipFor($hr);

        $this->actingAs($hr)->get(route('payroll.print', $slip))->assertOk()->assertDontSee('ดาวน์โหลด PDF');
        $this->actingAs($hr)->get(route('payroll.print', [$slip, 'download' => 'pdf']))->assertNotFound();
    }

    public function test_a_document_downloads_as_a_pdf_named_after_it(): void
    {
        $hr = $this->userWithRole('hr-manager', ['employee_code' => 'EMP-0042']);
        $slip = $this->slipFor($hr);

        $renderer = Mockery::mock(PdfRenderer::class);
        $renderer->shouldReceive('render')->once()
            ->withArgs(function (string $html) {
                // The PDF is made from the bare document: no toolbar, CSS and font inlined.
                return ! str_contains($html, '<header class="doc-bar"')
                    && str_contains($html, '.sheet')
                    && str_contains($html, 'data:font/ttf;base64,')
                    && str_contains($html, 'ใบแจ้งเงินเดือน');
            })
            ->andReturn('%PDF-1.4 fake');
        $this->app->instance(PdfRenderer::class, $renderer);

        $response = $this->actingAs($hr)->get(route('payroll.print', [$slip, 'download' => 'pdf']));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-1.4 fake', $response->getContent());
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('filename="EMP-0042_2569-05.pdf"', $disposition);
        $this->assertStringContainsString("filename*=UTF-8''".rawurlencode('สลิปเงินเดือน_EMP-0042_2569-05.pdf'), $disposition);
    }

    public function test_a_pdf_obeys_the_same_permissions_as_the_page(): void
    {
        $employee = $this->userWithRole('employee');
        $other = $this->userWithRole('employee');
        $theirs = $this->slipFor($other);
        $theirs->update(['status' => 'finalized']);

        $renderer = Mockery::mock(PdfRenderer::class);
        $renderer->shouldNotReceive('render');
        $this->app->instance(PdfRenderer::class, $renderer);

        $this->actingAs($employee)->get(route('payroll.print', [$theirs, 'download' => 'pdf']))->assertForbidden();
        $this->actingAs($employee)->get(route('my-payslips.print', [$theirs, 'download' => 'pdf']))->assertNotFound();
    }

    public function test_a_failed_render_falls_back_to_the_print_page_with_a_message(): void
    {
        $hr = $this->userWithRole('hr-manager');
        $slip = $this->slipFor($hr);

        $renderer = Mockery::mock(PdfRenderer::class);
        $renderer->shouldReceive('render')->andThrow(new \RuntimeException('boom'));
        $this->app->instance(PdfRenderer::class, $renderer);

        $this->actingAs($hr)->get(route('payroll.print', [$slip, 'download' => 'pdf']))
            ->assertRedirect(route('payroll.print', $slip))
            ->assertSessionHas('pdf_error');
    }

    /**
     * End to end with the real browser, where one is installed (CI images
     * and the Docker image have Chromium; skipped elsewhere).
     */
    public function test_chrome_renders_a_real_a4_pdf(): void
    {
        config(['hrpro.pdf.chrome_path' => null]);
        if (! PdfRenderer::available()) {
            $this->markTestSkipped('Chrome/Chromium is not installed here.');
        }

        $hr = $this->userWithRole('hr-manager');
        $slip = $this->slipFor($hr);

        $response = $this->actingAs($hr)->get(route('payroll.print', [$slip, 'download' => 'pdf']));
        $pdf = (string) $response->getContent();

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[\s*0 0 59[45]\.\d+ 84[12]\.\d+/', $pdf, 'A4 portrait');
        $this->assertStringContainsString('Sarabun', $pdf, 'the Thai document font is embedded');
    }
}
