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

    // Working hours used to flag late clock-ins / compute attendance status.
    'work' => [
        'start' => env('HRPRO_WORK_START', '09:00'),
        'end' => env('HRPRO_WORK_END', '18:00'),
        'late_grace_minutes' => (int) env('HRPRO_LATE_GRACE_MINUTES', 15),
        // Days counted as working days for leave duration (1 = Mon … 7 = Sun).
        'working_days' => [1, 2, 3, 4, 5],
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
