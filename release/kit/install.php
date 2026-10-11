<?php

/*
 * HR PRO V.2 — ตัวติดตั้งสำหรับทดลองใช้บนเครื่องตัวเอง
 *
 * เรียกผ่าน install-windows.bat หรือ install-mac-linux.sh ได้เลย
 * หรือสั่งเองด้วย:  php install.php [--demo | --no-demo] [--fresh]
 *
 * สิ่งที่ทำ: ตรวจ PHP → สร้าง .env (SQLite ไม่ต้องลง MySQL) → สร้างคีย์
 * → สร้างฐานข้อมูล + บัญชีผู้ดูแล (+ ข้อมูลตัวอย่างถ้าเลือก) → ลิงก์ที่เก็บไฟล์
 *
 * ใช้สำหรับเครื่องทดลอง (APP_ENV=local) เท่านั้น การขึ้น Production ดูใน START-HERE.html
 */

const MIN_PHP = '8.2.0';
const URL = 'http://127.0.0.1:8000';

chdir(__DIR__);

if (function_exists('sapi_windows_vt100_support')) {
    @sapi_windows_vt100_support(STDOUT, true);
}

function say(string $text = ''): void { echo $text, PHP_EOL; }
function head(string $text): void { say(); say("\033[1;36m▸ {$text}\033[0m"); }
function ok(string $text): void { say("  \033[32m✓\033[0m {$text}"); }
function warn(string $text): void { say("  \033[33m!\033[0m {$text}"); }
function fail(string $text, array $help = []): void
{
    say();
    say("  \033[1;31m✗ {$text}\033[0m");
    foreach ($help as $line) {
        say("    {$line}");
    }
    say();
    say('  ดูวิธีแก้เพิ่มเติมในไฟล์ START-HERE.html หัวข้อ "แก้ปัญหาที่พบบ่อย"');
    exit(1);
}

function ask(string $question, bool $default): bool
{
    $hint = $default ? '[Y/n]' : '[y/N]';
    echo "  {$question} {$hint} ";
    $answer = strtolower(trim((string) fgets(STDIN)));

    return $answer === '' ? $default : in_array($answer, ['y', 'yes', 'ใช่', 'ย'], true);
}

/** Runs `php artisan …` with this same PHP, showing its output. */
function artisan(string ...$args): void
{
    $process = proc_open([PHP_BINARY, 'artisan', ...$args, '--no-interaction'], [STDIN, STDOUT, STDERR], $pipes);
    if (! is_resource($process) || proc_close($process) !== 0) {
        fail('คำสั่ง php artisan '.implode(' ', $args).' ทำงานไม่สำเร็จ', ['อ่านข้อความผิดพลาดด้านบน']);
    }
}

/** Sets KEY=value in .env, replacing the line (and its trailing comment) if present. */
function setEnv(string $env, string $key, string $value): string
{
    $line = $key.'='.$value;
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

    return preg_match($pattern, $env) ? preg_replace($pattern, $line, $env) : rtrim($env).PHP_EOL.$line.PHP_EOL;
}

$args = array_slice($argv, 1);
$fresh = in_array('--fresh', $args, true);
$demo = in_array('--demo', $args, true) ? true : (in_array('--no-demo', $args, true) ? false : null);

say();
say("\033[1m  HR PRO V.2 — ติดตั้งสำหรับทดลองใช้งาน\033[0m");
say('  ─────────────────────────────────────');

// --- 1. PHP --------------------------------------------------------------
head('ตรวจสอบ PHP');

if (version_compare(PHP_VERSION, MIN_PHP, '<')) {
    fail('ต้องใช้ PHP '.MIN_PHP.' ขึ้นไป (เครื่องนี้มี '.PHP_VERSION.')', [
        'ติดตั้ง PHP รุ่นใหม่ตามขั้นตอนที่ 1 ใน START-HERE.html',
    ]);
}
ok('PHP '.PHP_VERSION);

$extensions = ['pdo_sqlite', 'openssl', 'fileinfo', 'tokenizer', 'dom', 'filter', 'session', 'iconv'];
$missing = array_values(array_filter($extensions, fn ($ext) => ! extension_loaded($ext)));
if ($missing) {
    $ini = php_ini_loaded_file() ?: '(ยังไม่มี php.ini — คัดลอก php.ini-development เป็น php.ini ในโฟลเดอร์ของ PHP)';
    fail('PHP ยังไม่ได้เปิดส่วนขยาย: '.implode(', ', $missing), [
        "เปิดไฟล์: {$ini}",
        'ค้นหาบรรทัดต่อไปนี้ แล้วลบเครื่องหมาย ; ด้านหน้าออก จากนั้นบันทึกและรันใหม่:',
        ...array_map(fn ($ext) => "    extension={$ext}", $missing),
    ]);
}
ok('ส่วนขยายที่จำเป็นครบ');

if (! is_file('vendor/autoload.php')) {
    fail('ไม่พบโฟลเดอร์ vendor', [
        'ถ้าดาวน์โหลดจาก ZIP: แตกไฟล์ใหม่ให้ครบทั้งโฟลเดอร์',
        'ถ้า clone จาก GitHub: รัน  composer install --no-dev  ก่อน',
    ]);
}

// --- 2. .env -------------------------------------------------------------
head('ตั้งค่าระบบ (.env)');

$database = 'database/database.sqlite';
$installed = is_file('.env') && is_file($database) && filesize($database) > 0;

if ($installed && ! $fresh) {
    say('  พบการติดตั้งเดิมอยู่แล้ว');
    $fresh = ask('ต้องการล้างข้อมูลทั้งหมดแล้วติดตั้งใหม่หรือไม่? (ตอบ n = เก็บข้อมูลเดิม แค่อัปเดตระบบ)', false);
}

if (! is_file('.env') || $fresh) {
    if (! is_file('.env.example')) {
        fail('ไม่พบไฟล์ .env.example', ['แตกไฟล์ ZIP ใหม่ให้ครบทั้งโฟลเดอร์']);
    }
    $env = file_get_contents('.env.example');
    $env = setEnv($env, 'APP_ENV', 'local');
    $env = setEnv($env, 'APP_DEBUG', 'true');
    $env = setEnv($env, 'APP_URL', URL);
    $env = setEnv($env, 'APP_KEY', '');
    $env = setEnv($env, 'DB_CONNECTION', 'sqlite');
    // SQLite reads database/database.sqlite when DB_DATABASE is unset.
    $env = preg_replace('/^(DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=.*)$/m', '# $1', $env);

    $demo ??= ask('โหลดข้อมูลตัวอย่าง (พนักงาน 47 คน การลา เงินเดือน ฯลฯ) เพื่อทดลองดูระบบหรือไม่?', true);
    $env = setEnv($env, 'SEED_DEMO', $demo ? 'true' : 'false');

    file_put_contents('.env', $env);
    ok('สร้างไฟล์ .env (ใช้ฐานข้อมูล SQLite ไม่ต้องติดตั้ง MySQL)');
} else {
    ok('ใช้ไฟล์ .env เดิม');
    $demo = (bool) preg_match('/^SEED_DEMO=true/m', file_get_contents('.env'));
}

if (! preg_match('/^APP_KEY=base64:/m', file_get_contents('.env'))) {
    artisan('key:generate', '--force');
}
ok('คีย์เข้ารหัสพร้อม');

// --- 3. Database ---------------------------------------------------------
head('สร้างฐานข้อมูล');

if ($fresh && is_file($database)) {
    unlink($database);
}
if (! is_file($database)) {
    touch($database);
}

foreach (['storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    is_dir($dir) || mkdir($dir, 0775, true);
}
artisan('config:clear');

if ($installed && ! $fresh) {
    artisan('migrate', '--force');
    ok('อัปเดตโครงสร้างฐานข้อมูลแล้ว ข้อมูลเดิมยังอยู่ครบ');
} else {
    if ($demo) {
        say('  กำลังสร้างข้อมูลตัวอย่าง อาจใช้เวลาราว 1 นาที…');
    }
    artisan('migrate', '--seed', '--force');
    ok('สร้างตารางและบัญชีผู้ดูแลระบบแล้ว');
}

// --- 4. Uploads ----------------------------------------------------------
if (! file_exists('public/storage')) {
    artisan('storage:link');
}
ok('พร้อมเก็บรูปภาพและไฟล์แนบ');

// --- Done ----------------------------------------------------------------
say();
say("\033[1;32m  ติดตั้งเสร็จเรียบร้อย\033[0m");
say('  ─────────────────────────────────────');
say('  ขั้นต่อไป: เปิดระบบด้วย start-windows.bat (Windows) หรือ  sh start-mac-linux.sh');
say('  แล้วเข้า '.URL);
say();
$env = file_get_contents('.env');
$email = preg_match('/^ADMIN_EMAIL=(.+)$/m', $env, $m) ? trim($m[1], " 	\"'") : 'admin@hrpro.local';
$password = preg_match('/^ADMIN_PASSWORD=(.+)$/m', $env, $m) ? trim($m[1], " 	\"'") : 'password';

say('  บัญชีสำหรับเข้าสู่ระบบจะแสดงอยู่ใต้ฟอร์มในหน้าเข้าสู่ระบบ (คลิกเพื่อกรอกอัตโนมัติ)');
say("  ผู้ดูแลระบบ : {$email}  รหัสผ่าน: {$password}");
if ($demo) {
    say('  ฝ่ายบุคคล   : hr@hrpro.local');
    say('  หัวหน้างาน  : manager@hrpro.local');
    say('  พนักงาน     : employee@hrpro.local   (ทุกบัญชีใช้รหัสผ่าน password)');
}
say();
