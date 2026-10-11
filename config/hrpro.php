<?php

/*
|--------------------------------------------------------------------------
| HR PRO — Domain Configuration
|--------------------------------------------------------------------------
| Business rules that an administrator may want to tune without touching
| code. All values are read from the environment with safe defaults.
*/

return [

    'company_name' => env('HRPRO_COMPANY_NAME', 'HR PRO Co., Ltd.'),

    // The administrator `db:seed` creates on a clean install. Left blank, the
    // password is the well-known demo one on a local install, and a random
    // one-time password on production (see App\Support\DefaultLogin).
    'admin' => [
        'name' => env('ADMIN_NAME', 'ผู้ดูแลระบบ HR PRO'),
        'email' => env('ADMIN_EMAIL', 'admin@hrpro.local'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Public demo: load the sample dataset and keep the demo logins, even
    // when APP_ENV=production.
    'demo' => filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOL),

    // Letterhead and signatory printed on official documents (payslips,
    // leave forms, certificates, registers). Blank fields are left off.
    'company' => [
        'name' => env('HRPRO_COMPANY_NAME', 'HR PRO Co., Ltd.'),
        'name_en' => env('HRPRO_COMPANY_NAME_EN'),
        'address' => env('HRPRO_COMPANY_ADDRESS'),
        'tax_id' => env('HRPRO_COMPANY_TAX_ID'),
        'phone' => env('HRPRO_COMPANY_PHONE'),
        'email' => env('HRPRO_COMPANY_EMAIL'),
        // Image under public/, e.g. images/logo.png. Initials are drawn without one.
        'logo' => env('HRPRO_COMPANY_LOGO'),
        // The one accent colour documents use (#rrggbb).
        'color' => env('HRPRO_COMPANY_COLOR', '#0e8f88'),
        // Who signs certificates and payslips on the company's behalf.
        'signatory_name' => env('HRPRO_SIGNATORY_NAME'),
        'signatory_title' => env('HRPRO_SIGNATORY_TITLE', 'ผู้จัดการฝ่ายทรัพยากรบุคคล'),
    ],

    // Working hours used to flag late clock-ins / compute attendance status.
    'work' => [
        'start' => env('HRPRO_WORK_START', '09:00'),
        'end' => env('HRPRO_WORK_END', '18:00'),
        'late_grace_minutes' => (int) env('HRPRO_LATE_GRACE_MINUTES', 15),
        // Leaving within this many minutes of the end counts as a full day.
        'early_grace_minutes' => (int) env('HRPRO_EARLY_GRACE_MINUTES', 15),
        // Days counted as working days for leave duration (1 = Mon … 7 = Sun).
        'working_days' => [1, 2, 3, 4, 5],
    ],

    // Clock-in geofencing. Administrators draw the allowed areas under
    // Settings → Work locations; these switches decide how strictly a GPS
    // reading is checked against them.
    'attendance' => [
        // Reject punches taken outside every active zone. Ignored while no
        // zone exists, so a fresh install is not locked out of the clock.
        'require_location' => filter_var(env('HRPRO_ATTENDANCE_REQUIRE_LOCATION', true), FILTER_VALIDATE_BOOL),
        // Largest GPS error (metres) still trusted for an enforced punch.
        'max_accuracy_meters' => (int) env('HRPRO_ATTENDANCE_MAX_ACCURACY', 200),
        // Outside every zone: punch recorded as off-site, or refused outright.
        'allow_off_site' => filter_var(env('HRPRO_ATTENDANCE_ALLOW_OFF_SITE', true), FILTER_VALIDATE_BOOL),
        // Whether the explanations asked for at the punch must be filled in.
        'late_reason_required' => false,
        'early_reason_required' => false,
        'off_site_reason_required' => false,
        // Punch photos older than this many days are deleted nightly by
        // `attendance:prune-photos`. Null keeps them.
        'photo_retention_days' => null,
    ],

    // Everything below is adjustable on the settings screen; these are the
    // defaults a fresh install starts from.
    'leave' => [
        // How many days back a request may start. Null allows any date.
        'backdate_days' => null,
        'reason_required' => true,
        'attachment_max_mb' => 4,
    ],

    'payroll' => [
        // Monthly salary ÷ this = one day's pay, used for unpaid leave.
        'days_per_month' => 30,
        // Day of the month salaries are paid, printed on payslips.
        'pay_day' => null,
        'social_security' => [
            'enabled' => false,
            'rate' => 5,
            'wage_cap' => 15000,
        ],
    ],

    'documents' => [
        'max_upload_mb' => 10,
        'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
    ],

    'notifications' => [
        'email' => true,
    ],

    'security' => [
        'password_min_length' => 8,
        'password_letters_numbers' => true,
        'password_mixed_case' => false,
        'password_symbols' => false,
        'login_max_attempts' => 5,
        'lockout_minutes' => 1,
    ],

    // The system's own logo, set in Settings → Logo by the super-admin. Paths
    // are under public/. Without a logo the "HR" monogram is drawn.
    'branding' => [
        'logo' => env('HRPRO_LOGO'),
        // Browser tab icon; falls back to the logo.
        'favicon' => env('HRPRO_FAVICON'),
        // Print the logo on documents when no company logo is set.
        'use_on_documents' => true,
    ],

    // Look of new accounts and of the sign-in screen.
    'appearance' => [
        'theme' => 'teal',
        'color_scheme' => 'light',
    ],

    // Leave entitlement year — month it resets on (1 = January).
    'leave_year_start_month' => (int) env('HRPRO_LEAVE_YEAR_START_MONTH', 1),

    // UI default language.
    'default_locale' => env('HRPRO_DEFAULT_LOCALE', 'th'),

    // Pagination default page size (lists never load more than this at once).
    'per_page' => 15,

    // Employee code format: prefix + zero-padded sequence.
    'employee_code' => [
        'prefix' => 'EMP-',
        'pad' => 4,
    ],
];
