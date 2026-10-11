<?php

use App\Models\Attendance;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SelfieStore;
use App\Support\DefaultLogin;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | Punch photos are personal data. Once they are older than the retention
 | period set in Settings → Attendance they are deleted, and the attendance
 | row keeps everything except the photo.
 */
Artisan::command('attendance:prune-photos', function (SelfieStore $selfies) {
    $days = (int) config('hrpro.attendance.photo_retention_days');

    if ($days < 1) {
        $this->info('ไม่ได้กำหนดระยะเวลาเก็บภาพถ่าย — ข้าม');

        return;
    }

    $cutoff = now()->subDays($days)->toDateString();
    $count = 0;

    Attendance::query()
        ->whereDate('work_date', '<', $cutoff)
        ->where(fn ($query) => $query->whereNotNull('clock_in_photo')->orWhereNotNull('clock_out_photo'))
        ->chunkById(200, function ($rows) use ($selfies, &$count) {
            foreach ($rows as $row) {
                $selfies->delete($row->clock_in_photo);
                $selfies->delete($row->clock_out_photo);
                $row->forceFill(['clock_in_photo' => null, 'clock_out_photo' => null])->save();
                $count++;
            }
        });

    $this->info("ลบภาพถ่ายลงเวลา {$count} รายการที่เก่ากว่า {$days} วัน");
})->purpose('Delete punch photos older than the retention period set in Settings');

Schedule::command('attendance:prune-photos')->dailyAt('02:30');

/*
 | Way back in for a locked-out administrator, or the first production
 | sign-in when the seeder's output was missed: a random one-time password
 | that has to be replaced at the next sign-in.
 */
Artisan::command('hrpro:admin-password {email? : อีเมลของบัญชี (ค่าเริ่มต้น: ADMIN_EMAIL)}', function () {
    $email = $this->argument('email') ?: config('hrpro.admin.email');
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("ไม่พบบัญชี {$email}");

        return 1;
    }

    $password = DefaultLogin::generate();
    $user->forceFill([
        'password' => $password,
        'must_change_password' => true,
        'is_active' => true,
    ])->save();

    AuditLogger::log('updated', "ออกรหัสผ่านชั่วคราวใหม่ให้ {$email} จาก console", $user);

    $this->newLine();
    $this->line("  อีเมล      : <info>{$email}</info>");
    $this->line("  รหัสผ่าน   : <info>{$password}</info>");
    $this->warn('  รหัสผ่านชั่วคราวนี้แสดงเพียงครั้งเดียว — ระบบจะให้ตั้งรหัสผ่านใหม่ทันทีที่เข้าสู่ระบบ');
    $this->newLine();

    return 0;
})->purpose('Issue a one-time password for an account (default: the admin), to be replaced at next sign-in');
