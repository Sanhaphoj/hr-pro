<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use App\Services\PayrollService;
use App\Support\SettingsCatalogue;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The settings screen: who may change which group, and that each setting
 * really changes what the system does.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $roleSlug, bool $withProfile = false): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([Role::where('slug', $roleSlug)->value('id')]);

        if ($withProfile) {
            Employee::factory()->create(['user_id' => $user->id]);
        }

        return $user->fresh();
    }

    private function workPayload(array $overrides = []): array
    {
        return $overrides + [
            'start' => '09:00',
            'end' => '18:00',
            'working_days' => [1, 2, 3, 4, 5],
            'late_grace_minutes' => 15,
            'early_grace_minutes' => 15,
        ];
    }

    // ------------------------------------------------------------- catalogue --

    public function test_every_setting_points_at_a_real_config_entry_and_permission(): void
    {
        foreach (SettingsCatalogue::fields() as $key => $field) {
            foreach ((array) $field['config'] as $path) {
                $this->assertTrue(config()->has($path), "{$key} → {$path}");
            }
        }

        foreach (SettingsCatalogue::groups() as $key => $group) {
            if ($group['super_admin_only'] ?? false) {
                $this->assertNull($group['permission'], $key);

                continue;
            }
            $this->assertTrue(Permission::where('name', $group['permission'])->exists(), $key);
        }
    }

    // ---------------------------------------------------------------- access --

    public function test_everyone_reaches_the_hub_but_sees_only_their_own_groups(): void
    {
        $this->actingAs($this->user('employee'))->get(route('settings.index'))
            ->assertOk()
            ->assertSee('บัญชีของฉัน')
            ->assertDontSee(route('settings.system.edit', 'company'), false);

        $this->actingAs($this->user('hr-manager'))->get(route('settings.index'))
            ->assertOk()
            ->assertSee(route('settings.system.edit', 'company'), false)
            ->assertSee(route('settings.system.edit', 'payroll'), false)
            ->assertDontSee(route('settings.system.edit', 'security'), false)
            ->assertDontSee(route('settings.system.edit', 'system'), false);

        $response = $this->actingAs($this->user('super-admin'))->get(route('settings.index'))->assertOk();
        foreach (SettingsCatalogue::keys() as $key) {
            $response->assertSee(route('settings.system.edit', $key), false);
        }
    }

    public function test_each_group_is_guarded_by_its_own_permission(): void
    {
        $employee = $this->user('employee');
        $this->actingAs($employee)->get(route('settings.system.edit', 'company'))->assertForbidden();
        $this->actingAs($employee)->put(route('settings.system.update', 'company'), ['name' => 'x', 'color' => '#000000'])->assertForbidden();

        $hr = $this->user('hr-manager');
        $this->actingAs($hr)->get(route('settings.system.edit', 'leave'))->assertOk();
        $this->actingAs($hr)->get(route('settings.system.edit', 'security'))->assertForbidden();
        $this->actingAs($hr)->delete(route('settings.system.reset', 'system'))->assertForbidden();
    }

    public function test_a_super_admin_can_open_every_group(): void
    {
        $admin = $this->user('super-admin');

        foreach (SettingsCatalogue::keys() as $key) {
            $this->actingAs($admin)->get(route('settings.system.edit', $key))->assertOk();
        }
    }

    public function test_the_role_editor_offers_the_settings_permissions(): void
    {
        $this->actingAs($this->user('super-admin'))
            ->get(route('settings.roles.edit', Role::where('slug', 'hr-manager')->first()))
            ->assertOk()
            ->assertSee('การตั้งค่าระบบ')
            ->assertSee('ตั้งค่าความปลอดภัยและรหัสผ่าน');
    }

    // ------------------------------------------------------- saving & reset --

    public function test_saving_the_company_name_takes_effect_and_is_audited(): void
    {
        $admin = $this->user('super-admin');

        $this->actingAs($admin)->put(route('settings.system.update', 'company'), [
            'name' => 'บริษัท ทดสอบระบบ จำกัด',
            'color' => '#123456',
            'address' => '99 ถนนสุขุมวิท กรุงเทพฯ',
        ])->assertRedirect(route('settings.system.edit', 'company'))->assertSessionHas('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');

        $this->assertSame('บริษัท ทดสอบระบบ จำกัด', config('hrpro.company_name'));
        $this->assertSame('#123456', config('hrpro.company.color'));
        $this->assertDatabaseHas('settings', ['key' => 'company.name', 'updated_by' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['description' => 'แก้ไขการตั้งค่า: ข้อมูลบริษัท']);

        $this->actingAs($admin)->get(route('dashboard'))->assertSee('บริษัท ทดสอบระบบ จำกัด');
        $this->actingAs($admin)->get(route('settings.system.edit', 'company'))->assertSee('กำหนดเอง');
    }

    public function test_a_value_equal_to_the_default_is_not_stored(): void
    {
        $this->actingAs($this->user('super-admin'))->put(route('settings.system.update', 'company'), [
            'name' => config('hrpro.company.name'),
            'color' => config('hrpro.company.color'),
        ])->assertSessionHas('success', 'ไม่มีการเปลี่ยนแปลง');

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_restoring_defaults_brings_the_file_values_back(): void
    {
        $admin = $this->user('super-admin');
        $original = config('hrpro.company_name');

        $this->actingAs($admin)->put(route('settings.system.update', 'company'), ['name' => 'ชื่อชั่วคราว', 'color' => '#abcdef']);
        $this->assertSame('ชื่อชั่วคราว', config('hrpro.company_name'));

        $this->actingAs($admin)->delete(route('settings.system.reset', 'company'))->assertSessionHas('success');

        $this->assertSame($original, config('hrpro.company_name'));
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_invalid_values_are_refused(): void
    {
        $this->actingAs($this->user('super-admin'))
            ->put(route('settings.system.update', 'work'), $this->workPayload([
                'start' => '18:00',
                'end' => '09:00',
                'working_days' => [],
                'late_grace_minutes' => 999,
            ]))
            ->assertSessionHasErrors(['end', 'working_days', 'late_grace_minutes']);

        $this->actingAs($this->user('super-admin'))
            ->put(route('settings.system.update', 'company'), ['name' => 'ทดสอบ', 'color' => 'red'])
            ->assertSessionHasErrors('color');

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_the_smtp_password_is_stored_encrypted_and_kept_when_left_blank(): void
    {
        $admin = $this->user('super-admin');
        $payload = [
            'email_enabled' => '1',
            'from_name' => 'ฝ่ายบุคคล',
            'from_address' => 'hr@example.com',
            'mailer' => 'smtp',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => 'mailer',
        ];

        $this->actingAs($admin)->put(route('settings.system.update', 'notifications'), $payload + ['smtp_password' => 's3cret-pass'])
            ->assertSessionHasNoErrors();

        $raw = DB::table('settings')->where('key', 'notifications.smtp_password')->value('value');
        $this->assertStringNotContainsString('s3cret-pass', (string) $raw);
        $this->assertSame('s3cret-pass', config('mail.mailers.smtp.password'));

        // Saving again without typing it keeps the stored one.
        $this->actingAs($admin)->put(route('settings.system.update', 'notifications'), $payload + ['smtp_host' => 'smtp2.example.com']);
        $this->assertSame('s3cret-pass', config('mail.mailers.smtp.password'));

        $this->actingAs($admin)->get(route('settings.system.edit', 'notifications'))
            ->assertOk()
            ->assertDontSee('s3cret-pass');
    }

    public function test_a_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('branding');
        $admin = $this->user('super-admin');

        $this->actingAs($admin)->put(route('settings.system.update', 'company'), [
            'name' => 'บริษัท โลโก้ จำกัด',
            'color' => '#0e8f88',
            'logo' => UploadedFile::fake()->create('logo.png', 40, 'image/png'),
        ])->assertSessionHasNoErrors();

        $path = config('hrpro.company.logo');
        $this->assertStringStartsWith('uploads/branding/logo-', $path);
        Storage::disk('branding')->assertExists(basename($path));

        $this->actingAs($admin)->put(route('settings.system.update', 'company'), [
            'name' => 'บริษัท โลโก้ จำกัด',
            'color' => '#0e8f88',
            'logo_remove' => '1',
        ]);

        Storage::disk('branding')->assertMissing(basename($path));
        $this->assertNull(config('hrpro.company.logo'));
    }

    // ------------------------------------------------------------ system logo --

    public function test_the_system_logo_is_for_the_super_admin_alone(): void
    {
        // Even a role handed every settings permission does not get the logo.
        Role::where('slug', 'hr-manager')->first()->permissions()
            ->syncWithoutDetaching(Permission::where('group', 'settings')->pluck('id'));
        $hr = $this->user('hr-manager');

        $this->actingAs($hr)->get(route('settings.index'))
            ->assertOk()
            ->assertSee(route('settings.system.edit', 'system'), false)
            ->assertDontSee(route('settings.system.edit', 'branding'), false);
        $this->actingAs($hr)->get(route('settings.system.edit', 'branding'))->assertForbidden();
        $this->actingAs($hr)->put(route('settings.system.update', 'branding'), ['use_on_documents' => '0'])->assertForbidden();

        $this->actingAs($this->user('super-admin'))->get(route('settings.system.edit', 'branding'))
            ->assertOk()
            ->assertSee('โลโก้ระบบ');
    }

    public function test_an_uploaded_logo_replaces_the_monogram_across_the_system(): void
    {
        Storage::fake('branding');
        $admin = $this->user('super-admin', true);

        $this->actingAs($admin)->put(route('settings.system.update', 'branding'), [
            'logo' => UploadedFile::fake()->create('brand.png', 40, 'image/png'),
            'use_on_documents' => '1',
        ])->assertSessionHasNoErrors();

        $path = config('hrpro.branding.logo');
        $this->assertStringStartsWith('uploads/branding/logo-', $path);
        Storage::disk('branding')->assertExists(basename($path));

        // The fake disk is not public/, so put a real file where the pages look for it.
        File::ensureDirectoryExists(dirname(public_path($path)));
        File::put(public_path($path), 'png');

        try {
            $this->actingAs($admin)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('class="logo logo--img"', false)
                ->assertSee('rel="icon"', false);

            $this->app['auth']->guard()->logout();
            $this->get(route('login'))->assertOk()->assertSee($path, false);
        } finally {
            File::delete(public_path($path));
        }
    }

    public function test_documents_fall_back_to_the_system_logo_when_allowed(): void
    {
        $admin = $this->user('super-admin', true);
        $path = 'uploads/branding/test-'.uniqid().'.png';
        File::ensureDirectoryExists(public_path('uploads/branding'));
        File::put(public_path($path), 'png');
        config(['hrpro.branding.logo' => $path, 'hrpro.branding.use_on_documents' => true, 'hrpro.company.logo' => null]);

        try {
            $this->actingAs($admin)->get(route('employees.certificate', $admin->employee))
                ->assertOk()
                ->assertSee('lh__logo lh__logo--img', false);

            config(['hrpro.branding.use_on_documents' => false]);
            $this->actingAs($admin)->get(route('employees.certificate', $admin->employee))
                ->assertOk()
                ->assertDontSee('lh__logo lh__logo--img', false);
        } finally {
            File::delete(public_path($path));
        }
    }

    // ---------------------------------------------------------------- effects --

    public function test_work_hours_move_the_late_threshold(): void
    {
        $this->actingAs($this->user('super-admin'))
            ->put(route('settings.system.update', 'work'), $this->workPayload([
                'start' => '08:00',
                'end' => '17:00',
                'working_days' => ['1', '2', '3', '4', '5', '6'],
                'late_grace_minutes' => '5',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('08:05', app(AttendanceService::class)->lateThreshold(Carbon::parse('2026-09-14'))->format('H:i'));
        $this->assertSame([1, 2, 3, 4, 5, 6], config('hrpro.work.working_days'));
    }

    public function test_off_site_punches_can_be_refused(): void
    {
        $this->actingAs($this->user('super-admin'))->put(route('settings.system.update', 'attendance'), [
            'require_location' => '1',
            'allow_off_site' => '0',
            'max_accuracy_meters' => '200',
            'late_reason_required' => '0',
            'early_reason_required' => '0',
            'off_site_reason_required' => '0',
            'photo_retention_days' => '',
        ])->assertSessionHasNoErrors();

        WorkLocation::factory()->create(['latitude' => 13.7248, 'longitude' => 100.5340, 'radius_meters' => 100]);
        $employee = $this->user('employee', true);

        $this->actingAs($employee)->post(route('time-clock.store'), [
            'action' => 'in',
            'latitude' => 13.7278,
            'longitude' => 100.5340,
            'accuracy' => 10,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_a_required_late_reason_is_enforced(): void
    {
        Carbon::setTestNow('2026-09-14 11:00:00');
        config(['hrpro.attendance.late_reason_required' => true]);
        $employee = $this->user('employee', true);

        $this->actingAs($employee)->post(route('time-clock.store'), ['action' => 'in'])
            ->assertSessionHas('error', 'กรุณาระบุเหตุผลที่มาสายก่อนลงเวลา');
        $this->assertDatabaseCount('attendances', 0);

        $this->actingAs($employee)->post(route('time-clock.store'), ['action' => 'in', 'reason' => 'รถติด'])
            ->assertSessionHas('success');
    }

    public function test_leave_backdating_and_an_optional_reason_follow_the_settings(): void
    {
        Carbon::setTestNow('2026-09-14 09:00:00');
        config(['hrpro.leave.backdate_days' => 3, 'hrpro.leave.reason_required' => false]);

        $employee = $this->user('employee', true);
        $type = LeaveType::factory()->create(['days_per_year' => 10, 'requires_approval' => true]);

        $this->actingAs($employee)->post(route('leave-requests.store'), [
            'leave_type_id' => $type->id,
            'start_date' => '2026-09-04',
            'end_date' => '2026-09-04',
            'reason' => 'ย้อนหลังเกินกำหนด',
        ])->assertSessionHasErrors('start_date');

        $this->actingAs($employee)->post(route('leave-requests.store'), [
            'leave_type_id' => $type->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-15',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['employee_id' => $employee->employee->id, 'reason' => '']);
    }

    public function test_social_security_is_deducted_when_switched_on(): void
    {
        config([
            'hrpro.payroll.social_security.enabled' => true,
            'hrpro.payroll.social_security.rate' => 5,
            'hrpro.payroll.social_security.wage_cap' => 15000,
            'hrpro.payroll.withholding_tax.enabled' => false,   // SSO alone here
        ]);
        Employee::factory()->create(['base_salary' => 30000]);

        app(PayrollService::class)->generateForPeriod(2026, 9);

        $row = Payroll::first();
        $this->assertEquals(750.0, (float) $row->social_security);
        $this->assertEquals(750.0, (float) $row->deductions);
        $this->assertEquals(29250.0, (float) $row->net_pay);
    }

    public function test_the_password_policy_follows_the_settings(): void
    {
        config(['hrpro.security.password_min_length' => 12]);
        $admin = $this->user('super-admin');

        $this->actingAs($admin)->post(route('settings.users.store'), [
            'name' => 'ผู้ใช้ใหม่',
            'email' => 'short@example.com',
            'password' => 'abc12345',
            'password_confirmation' => 'abc12345',
        ])->assertSessionHasErrors('password');

        $this->actingAs($admin)->post(route('settings.users.store'), [
            'name' => 'ผู้ใช้ใหม่',
            'email' => 'long@example.com',
            'password' => 'abcdef123456',
            'password_confirmation' => 'abcdef123456',
        ])->assertSessionHasNoErrors();
    }

    public function test_login_lockout_follows_the_attempt_limit(): void
    {
        config(['hrpro.security.login_max_attempts' => 3]);
        $user = User::factory()->create(['email' => 'lock@example.com']);

        foreach (range(1, 3) as $attempt) {
            $this->post(route('login.store'), ['email' => 'lock@example.com', 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['email' => 'lock@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_notifications_can_be_switched_off(): void
    {
        Mail::fake();
        config(['hrpro.notifications.email' => false]);
        $user = $this->user('employee');

        app(NotificationService::class)->notify($user, 'หัวข้อ', 'ข้อความ');

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'title' => 'หัวข้อ']);
    }

    public function test_a_test_email_goes_to_the_admin_asking(): void
    {
        Mail::fake();
        $admin = $this->user('super-admin');

        $this->actingAs($admin)->post(route('settings.system.test-mail'))->assertSessionHas('success');

        Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($admin->email));
    }

    public function test_new_accounts_start_on_the_default_appearance(): void
    {
        config(['hrpro.appearance.theme' => 'navy', 'hrpro.appearance.color_scheme' => 'dark']);

        $this->assertSame('navy', User::factory()->create()->theme);
        $this->get(route('login'))->assertSee('data-theme="navy"', false)->assertSee('data-scheme="dark"', false);
    }

    public function test_old_punch_photos_are_pruned_after_the_retention_period(): void
    {
        Storage::fake('local');
        config(['hrpro.attendance.photo_retention_days' => 30]);
        $employee = Employee::factory()->create();

        Storage::disk('local')->put('attendance-photos/old.jpg', 'x');
        Storage::disk('local')->put('attendance-photos/new.jpg', 'x');
        $old = Attendance::create(['employee_id' => $employee->id, 'work_date' => now()->subDays(40)->toDateString(), 'status' => 'present', 'clock_in_photo' => 'attendance-photos/old.jpg']);
        Attendance::create(['employee_id' => $employee->id, 'work_date' => now()->toDateString(), 'status' => 'present', 'clock_in_photo' => 'attendance-photos/new.jpg']);

        $this->artisan('attendance:prune-photos')->assertSuccessful();

        Storage::disk('local')->assertMissing('attendance-photos/old.jpg');
        Storage::disk('local')->assertExists('attendance-photos/new.jpg');
        $this->assertNull($old->fresh()->clock_in_photo);
    }
}
