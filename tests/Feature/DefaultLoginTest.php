<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultLoginTest extends TestCase
{
    use RefreshDatabase;

    private function seedAdmin(): User
    {
        // --force: production asks before seeding.
        foreach ([RolePermissionSeeder::class, AdminUserSeeder::class] as $seeder) {
            $this->artisan('db:seed', ['--class' => $seeder, '--force' => true])->assertSuccessful();
        }

        return User::where('email', 'admin@hrpro.local')->firstOrFail();
    }

    private function asProduction(bool $demo = false, ?string $password = null): void
    {
        $this->app['env'] = 'production';
        config(['hrpro.demo' => $demo, 'hrpro.admin.password' => $password]);
    }

    // ----- Local / demo install ---------------------------------------------

    public function test_local_admin_uses_the_demo_password_without_a_forced_change(): void
    {
        $admin = $this->seedAdmin();

        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertFalse($admin->must_change_password);
        $this->assertTrue($admin->hasRole('super-admin'));
    }

    public function test_login_screen_lists_the_demo_login_on_a_local_install(): void
    {
        $this->seedAdmin();

        $this->get('/login')
            ->assertOk()
            ->assertSee('บัญชีทดลอง')
            ->assertSee('data-fill-email="admin@hrpro.local"', false);
    }

    public function test_demo_login_disappears_once_its_password_is_changed(): void
    {
        $admin = $this->seedAdmin();
        $admin->update(['password' => 'my-own-pass-123']);

        $this->get('/login')->assertOk()->assertDontSee('บัญชีทดลอง');
    }

    public function test_public_demo_on_production_keeps_the_demo_login(): void
    {
        $this->asProduction(demo: true);
        $admin = $this->seedAdmin();

        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertFalse($admin->must_change_password);
        $this->get('/login')->assertSee('บัญชีทดลอง');
    }

    // ----- Real production install --------------------------------------------

    public function test_production_admin_gets_a_random_one_time_password(): void
    {
        $this->asProduction();
        $admin = $this->seedAdmin();

        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertTrue($admin->must_change_password);
        $this->get('/login')->assertOk()->assertDontSee('บัญชีทดลอง');
    }

    public function test_production_ignores_the_well_known_password_from_env_example(): void
    {
        $this->asProduction(password: 'password');
        $admin = $this->seedAdmin();

        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertTrue($admin->must_change_password);
    }

    public function test_production_uses_a_configured_admin_password_but_still_forces_a_change(): void
    {
        $this->asProduction(password: 'Chosen-by-ops-42');
        $admin = $this->seedAdmin();

        $this->assertTrue(Hash::check('Chosen-by-ops-42', $admin->password));
        $this->assertTrue($admin->must_change_password);
    }

    public function test_seeding_again_leaves_an_existing_admin_alone(): void
    {
        $admin = $this->seedAdmin();
        $admin->update(['password' => 'my-own-pass-123']);

        $this->artisan('db:seed', ['--class' => AdminUserSeeder::class])->assertSuccessful();

        $this->assertTrue(Hash::check('my-own-pass-123', $admin->fresh()->password));
    }

    // ----- Forced password change ---------------------------------------------

    public function test_account_on_a_handed_out_password_is_held_at_the_change_screen(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)->get('/')->assertRedirect(route('password.force'));
        $this->actingAs($user)->get(route('password.force'))->assertOk()->assertSee('ตั้งรหัสผ่านใหม่');
    }

    public function test_new_password_must_differ_from_the_handed_out_one(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)
            ->from(route('password.force'))
            ->put(route('password.force.update'), ['password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_choosing_a_new_password_releases_the_account(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)
            ->put(route('password.force.update'), ['password' => 'brand-new-99', 'password_confirmation' => 'brand-new-99'])
            ->assertRedirect(route('time-clock.show'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('brand-new-99', $user->password));
        $this->actingAs($user)->get(route('password.force'))->assertRedirect(route('time-clock.show'));
    }

    // ----- Console recovery ------------------------------------------------------

    public function test_console_issues_a_one_time_password(): void
    {
        $admin = $this->seedAdmin();

        $this->artisan('hrpro:admin-password')
            ->expectsOutputToContain('admin@hrpro.local')
            ->assertSuccessful();

        $admin->refresh();
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertTrue($admin->must_change_password);
    }

    public function test_console_reports_an_unknown_account(): void
    {
        $this->artisan('hrpro:admin-password', ['email' => 'nobody@hrpro.local'])->assertFailed();
    }
}
