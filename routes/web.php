<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrgChartController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserController;
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

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated application routes
|--------------------------------------------------------------------------
| Every route below requires a logged-in, active account. Fine-grained
| permission checks are declared inside each controller (HasMiddleware).
*/
Route::middleware(['auth', 'active'])->group(function () {

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

    Route::get('/leave-approvals', [LeaveApprovalController::class, 'index'])->name('leave-approvals.index');
    Route::post('/leave-approvals/{leaveRequest}/approve', [LeaveApprovalController::class, 'approve'])->name('leave-approvals.approve');
    Route::post('/leave-approvals/{leaveRequest}/reject', [LeaveApprovalController::class, 'reject'])->name('leave-approvals.reject');

    // --- Attendance ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // --- Communication ---
    Route::resource('announcements', AnnouncementController::class);

    // --- Insight ---
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // --- HR Operations (Phase 2) ---
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');

    Route::get('/recruitment', [JobPostingController::class, 'index'])->name('recruitment.index');
    Route::get('/recruitment/create', [JobPostingController::class, 'create'])->name('recruitment.create');
    Route::post('/recruitment', [JobPostingController::class, 'store'])->name('recruitment.store');
    Route::get('/recruitment/{recruitment}', [JobPostingController::class, 'show'])->name('recruitment.show');
    Route::delete('/recruitment/{recruitment}', [JobPostingController::class, 'destroy'])->name('recruitment.destroy');
    Route::post('/recruitment/{recruitment}/candidates', [JobPostingController::class, 'storeCandidate'])->name('recruitment.candidates.store');
    Route::put('/recruitment/{recruitment}/candidates/{candidate}', [JobPostingController::class, 'updateCandidate'])->name('recruitment.candidates.update');

    Route::resource('performance', PerformanceReviewController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::get('/training/create', [TrainingController::class, 'create'])->name('training.create');
    Route::post('/training', [TrainingController::class, 'store'])->name('training.store');
    Route::get('/training/{training}', [TrainingController::class, 'show'])->name('training.show');
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

    // --- Language switch ---
    Route::get('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

    // --- Administration ---
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });
});
