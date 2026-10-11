<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\DefaultLogin;
use Illuminate\Database\Seeder;

/**
 * Creates the single administrator account required to log in to a fresh
 * install. Idempotent — safe to run repeatedly and alongside DemoDataSeeder.
 *
 * Credentials can be overridden via environment variables:
 *   ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
 *
 * Local / demo install: ADMIN_PASSWORD, or the well-known "password".
 * Production: ADMIN_PASSWORD if it is set to something other than the
 * well-known one, otherwise a random one-time password printed here once;
 * either way the administrator must choose a new password at first sign-in.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('hrpro.admin.email');
        $admin = User::where('email', $email)->first();

        if (! $admin) {
            $admin = $this->createAdmin($email);
        }

        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $admin->roles()->syncWithoutDetaching([$superAdmin->id]);
        }
    }

    private function createAdmin(string $email): User
    {
        $secure = ! DefaultLogin::enabled();
        $configured = config('hrpro.admin.password');
        $generated = $secure && ! DefaultLogin::isUsableInProduction($configured);

        $password = match (true) {
            $generated => DefaultLogin::generate(),
            $secure => $configured,
            default => DefaultLogin::adminPassword(),
        };

        $admin = User::create([
            'name' => config('hrpro.admin.name'),
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ]);
        $admin->forceFill([
            'email_verified_at' => now(),
            'must_change_password' => $secure,
        ])->save();

        $this->report($email, $secure ? ($generated ? $password : null) : $password, $secure);

        return $admin;
    }

    /**
     * Prints the first sign-in. A generated password is shown this once and
     * never stored in plain text; one taken from ADMIN_PASSWORD is not echoed.
     */
    private function report(string $email, ?string $password, bool $secure): void
    {
        $out = $this->command;
        if (! $out) {
            return;
        }

        $out->newLine();
        $out->line('  <options=bold>HR PRO — บัญชีผู้ดูแลระบบสำหรับเข้าสู่ระบบครั้งแรก</>');
        $out->line("  อีเมล      : <info>{$email}</info>");
        $out->line('  รหัสผ่าน   : '.($password !== null ? "<info>{$password}</info>" : 'ตามค่า ADMIN_PASSWORD ที่ตั้งไว้'));

        if ($secure) {
            if ($password !== null) {
                $out->warn('  รหัสผ่านนี้สุ่มขึ้นใหม่และจะแสดงเพียงครั้งเดียว — คัดลอกเก็บไว้ตอนนี้');
            }
            $out->line('  ระบบจะบังคับให้ตั้งรหัสผ่านใหม่ทันทีที่เข้าสู่ระบบครั้งแรก');
            $out->line('  ลืมรหัสผ่าน: php artisan hrpro:admin-password');
        }
        $out->newLine();
    }
}
