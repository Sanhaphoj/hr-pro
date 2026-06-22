<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the single administrator account required to log in to a fresh
 * install. Idempotent — safe to run repeatedly and alongside DemoDataSeeder.
 *
 * Credentials can be overridden via environment variables:
 *   ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('ADMIN_NAME', 'ผู้ดูแลระบบ HR PRO');
        $email = env('ADMIN_EMAIL', 'admin@hrpro.local');
        $password = env('ADMIN_PASSWORD', 'password');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $admin->roles()->syncWithoutDetaching([$superAdmin->id]);
        }
    }
}
