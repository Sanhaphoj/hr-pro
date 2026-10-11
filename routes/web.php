<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendancePhotoController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForcedPasswordController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MyPayslipController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrgChartController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SystemSettingsController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\WorkLocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public (guest) routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// Installable app (PWA). Public: the browser fetches it without cookies.
Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// An account on a handed-out password chooses its own here before anything
// else opens (see EnsurePasswordIsChanged).
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/password/change', [ForcedPasswordController::class, 'edit'])->name('password.force');
    Route::put('/password/change', [ForcedPasswordController::class, 'update'])->name('password.force.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated application routes
|--------------------------------------------------------------------------
| Every route below requires a logged-in, active account that has chosen its
| own password. Fine-grained permission checks are declared inside each
| controller (HasMiddleware).
*/
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Self-service: profile & password ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // --- Self-service: notifications ---
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // --- People management ---
    // Printable documents come first: `/employees/print` would otherwise be
    // read as an employee id by the resource's show route.
    Route::get('/employees/print', [EmployeeController::class, 'printList'])->name('employees.print-list')->middleware('pdf');
    Route::get('/employees/{employee}/print', [EmployeeController::class, 'print'])->name('employees.print')->middleware('pdf');
    Route::get('/employees/{employee}/certificate', [EmployeeController::class, 'certificate'])->name('employees.certificate')->middleware('pdf');
    Route::resource('employees', EmployeeController::class);
    Route::resource('departments', DepartmentController::class)->except('show');
    Route::resource('positions', PositionController::class)->except('show');

    // --- Organisation chart (open to all authenticated users) ---
    Route::get('/org-chart', [OrgChartController::class, 'index'])->name('org-chart.index');

    // --- Leave management ---
    Route::resource('leave-types', LeaveTypeController::class)
        ->except('show')
        ->parameters(['leave-types' => 'leaveType']);

    Route::resource('leave-requests', LeaveRequestController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['leave-requests' => 'leaveRequest']);
    Route::post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('leave-requests.cancel');
    Route::get('/leave-requests/{leaveRequest}/print', [LeaveRequestController::class, 'print'])
        ->name('leave-requests.print')->middleware('pdf');

    // Who is away this month: department colleagues for everyone, the whole
    // organisation for roles that read every request.
    Route::get('/leave-calendar', [LeaveCalendarController::class, 'index'])->name('leave-calendar.index');

    // Live record results for the Ctrl+K command palette.
    Route::get('/search', SearchController::class)->name('search')->middleware('throttle:60,1');

    // "ผู้ช่วย HR" (Claude). Throttled per person: every question is a paid API call.
    Route::post('/assistant', [AssistantController::class, 'ask'])->name('assistant.ask')->middleware('throttle:20,1');

    Route::get('/leave-approvals', [LeaveApprovalController::class, 'index'])->name('leave-approvals.index');
    Route::post('/leave-approvals/{leaveRequest}/approve', [LeaveApprovalController::class, 'approve'])->name('leave-approvals.approve');
    Route::post('/leave-approvals/{leaveRequest}/reject', [LeaveApprovalController::class, 'reject'])->name('leave-approvals.reject');

    // --- Attendance ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    // A monthly timesheet for one person, or (with ?date=) the day's register.
    Route::get('/attendance/print', [AttendanceController::class, 'print'])->name('attendance.print')->middleware('pdf');

    // Clocking in/out has its own screen so the browser can obtain a GPS fix
    // and the punch can be checked against the approved work locations.
    Route::get('/time-clock', [TimeClockController::class, 'show'])->name('time-clock.show');
    Route::post('/time-clock', [TimeClockController::class, 'store'])->name('time-clock.store');

    // Punch photos sit on the private disk; this is the only way to read one.
    Route::get('/attendance/{attendance}/photo/{kind}', [AttendancePhotoController::class, 'show'])
        ->whereIn('kind', ['in', 'out'])
        ->name('attendance.photo');

    Route::resource('work-locations', WorkLocationController::class)
        ->except('show')
        ->parameters(['work-locations' => 'workLocation']);

    // --- Communication ---
    Route::resource('announcements', AnnouncementController::class);
    Route::get('/announcements/{announcement}/print', [AnnouncementController::class, 'print'])->name('announcements.print')->middleware('pdf');

    // --- Insight ---
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // --- HR Operations (Phase 2) ---
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/print', [PayrollController::class, 'printRegister'])->name('payroll.print-register')->middleware('pdf');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('/payroll/{payroll}/print', [PayrollController::class, 'printSlip'])->name('payroll.print')->middleware('pdf');
    Route::put('/payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::post('/payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');

    // Self-service: one's own confirmed payslips, no payroll permission needed.
    Route::get('/my/payslips', [MyPayslipController::class, 'index'])->name('my-payslips.index');
    Route::get('/my/payslips/{payroll}', [MyPayslipController::class, 'print'])->name('my-payslips.print')->middleware('pdf');

    Route::get('/recruitment', [JobPostingController::class, 'index'])->name('recruitment.index');
    Route::get('/recruitment/create', [JobPostingController::class, 'create'])->name('recruitment.create');
    Route::post('/recruitment', [JobPostingController::class, 'store'])->name('recruitment.store');
    Route::get('/recruitment/{recruitment}', [JobPostingController::class, 'show'])->name('recruitment.show');
    Route::delete('/recruitment/{recruitment}', [JobPostingController::class, 'destroy'])->name('recruitment.destroy');
    Route::post('/recruitment/{recruitment}/candidates', [JobPostingController::class, 'storeCandidate'])->name('recruitment.candidates.store');
    Route::put('/recruitment/{recruitment}/candidates/{candidate}', [JobPostingController::class, 'updateCandidate'])->name('recruitment.candidates.update');

    Route::resource('performance', PerformanceReviewController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('/performance/{performance}/print', [PerformanceReviewController::class, 'print'])->name('performance.print')->middleware('pdf');

    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::get('/training/create', [TrainingController::class, 'create'])->name('training.create');
    Route::post('/training', [TrainingController::class, 'store'])->name('training.store');
    Route::get('/training/{training}', [TrainingController::class, 'show'])->name('training.show');
    Route::get('/training/{training}/print', [TrainingController::class, 'print'])->name('training.print')->middleware('pdf');
    Route::delete('/training/{training}', [TrainingController::class, 'destroy'])->name('training.destroy');
    Route::post('/training/{training}/enroll', [TrainingController::class, 'enroll'])->name('training.enroll');
    Route::post('/training/{training}/enrollments/{enrollment}/complete', [TrainingController::class, 'complete'])->name('training.complete');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // --- Self-service onboarding ---
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/tasks/{task}/toggle', [OnboardingController::class, 'toggle'])->name('onboarding.toggle');

    // --- Exports (CSV) ---
    Route::get('/export/employees', [ExportController::class, 'employees'])->name('export.employees');
    Route::get('/export/payroll', [ExportController::class, 'payroll'])->name('export.payroll');
    Route::get('/export/payroll/pnd1', [ExportController::class, 'pnd1'])->name('export.pnd1');
    Route::get('/export/payroll/sso', [ExportController::class, 'socialSecurity'])->name('export.sso');

    // --- Language switch ---
    Route::get('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

    // --- Administration ---
    Route::prefix('settings')->name('settings.')->group(function () {
        // The settings hub is open to everyone; each group behind it checks
        // its own permission in the controller.
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::post('system/notifications/test-mail', [SystemSettingsController::class, 'testMail'])->name('system.test-mail');
        Route::get('system/{group}', [SystemSettingsController::class, 'edit'])
            ->whereIn('group', \App\Support\SettingsCatalogue::keys())->name('system.edit');
        Route::put('system/{group}', [SystemSettingsController::class, 'update'])
            ->whereIn('group', \App\Support\SettingsCatalogue::keys())->name('system.update');
        Route::delete('system/{group}', [SystemSettingsController::class, 'reset'])
            ->whereIn('group', \App\Support\SettingsCatalogue::keys())->name('system.reset');

        // Appearance is self-service: every account may set its own.
        Route::get('appearance', [AppearanceController::class, 'edit'])->name('appearance.edit');
        Route::put('appearance', [AppearanceController::class, 'update'])->name('appearance.update');
        Route::patch('appearance/scheme', [AppearanceController::class, 'scheme'])->name('appearance.scheme');

        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });
});
