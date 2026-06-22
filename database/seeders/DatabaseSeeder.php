<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Always seed the structural data a working install needs:
        // roles/permissions and one administrator account to log in with.
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Sample/mock data is opt-in. Set SEED_DEMO=true to load the full
        // demo dataset (employees, leave, attendance, payroll, …) — used by
        // the public live demo. A normal install leaves this off so you start
        // with a clean database and connect your own data.
        if (filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
