<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeClockRequest;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use App\Services\GeofenceService;
use App\Services\SelfieStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The dedicated clock-in screen. It is deliberately separate from the
 * attendance history: the browser has to obtain a GPS fix before either
 * button unlocks, and every punch is checked against the areas an
 * administrator has approved (see WorkLocationController).
 */
class TimeClockController extends Controller
{
    public function show(AttendanceService $attendanceService, GeofenceService $geofence): View
    {
        $user = auth()->user();
        $employee = $user->employee;
        $zones = WorkLocation::active()->orderBy('name')->get();
        $canViewAll = $user->hasPermission('attendance.viewAll');

        $today = $employee
            ? $attendanceService->todayFor($employee)?->load(['clockInLocation', 'clockOutLocation'])
            : null;

        return view('time-clock.index', [
            'user' => $user,
            'employee' => $employee,
            'today' => $today,
            'zones' => $zones,
            // Trimmed to what the on-screen distance readout needs; the browser
            // gets no more of the geofence table than it has to have.
            'zonePayload' => $zones->map(fn (WorkLocation $zone) => [
                'name' => $zone->name,
                'lat' => $zone->latitude,
                'lng' => $zone->longitude,
                'radius' => $zone->radius_meters,
            ])->values(),
            'enforced' => (bool) config('hrpro.attendance.require_location', true) && $geofence->hasZones(),
            'maxAccuracy' => (int) config('hrpro.attendance.max_accuracy_meters', 200),
            'workStart' => config('hrpro.work.start', '09:00'),
            'workEnd' => config('hrpro.work.end', '18:00'),
            // ISO-8601 with offset so the browser compares against the same
            // instants the server will judge the punch by.
            'lateAfter' => $attendanceService->lateThreshold()->toIso8601String(),
            'earlyBefore' => $attendanceService->earlyThreshold()->toIso8601String(),
            // The organisation-wide strip is a supervisor view; an employee
            // only ever sees their own day.
            'summary' => $canViewAll ? $attendanceService->dailySummary() : null,
            // Set per role on the permissions screen.
            'selfieIn' => $employee !== null && $user->requiresSelfie('in'),
            'selfieOut' => $employee !== null && $user->requiresSelfie('out'),
            // Settings → Attendance.
            'allowOffSite' => (bool) config('hrpro.attendance.allow_off_site', true),
            'reasonRules' => [
                'late' => (bool) config('hrpro.attendance.late_reason_required', false),
                'early' => (bool) config('hrpro.attendance.early_reason_required', false),
                'offSite' => (bool) config('hrpro.attendance.off_site_reason_required', false),
            ],
        ]);
    }

    public function store(StoreTimeClockRequest $request, AttendanceService $attendanceService, SelfieStore $selfies): RedirectResponse
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'ยังไม่มีโปรไฟล์พนักงานที่เชื่อมโยงกับบัญชีของคุณ');
        }

        $clockingIn = $request->input('action') === 'in';
        $reason = $request->input('reason');
        $offSiteReason = $request->input('off_site_reason');
        $action = $clockingIn ? 'in' : 'out';

        // A role that requires a photo gets no punch without one. The file is
        // written first and removed again if the punch is then refused, so a
        // refusal leaves neither a row nor an orphaned photo behind. A photo
        // sent when none is required is ignored rather than kept.
        $photoPath = null;
        if ($user->requiresSelfie($action)) {
            $binary = $selfies->decode($request->input('photo'));
            if ($binary === null) {
                return back()->with('error', 'บทบาทของคุณต้องถ่ายภาพใบหน้าก่อนลงเวลา กรุณาอนุญาตการใช้กล้องแล้วลองใหม่');
            }
            $photoPath = $selfies->store($employee, $action, $binary);
        }

        try {
            $attendance = $clockingIn
                ? $attendanceService->clockIn($employee, $request->coordinates(), $reason, $offSiteReason)
                : $attendanceService->clockOut($employee, $request->coordinates(), $reason, $offSiteReason);
        } catch (ValidationException $e) {
            $selfies->delete($photoPath);

            return back()->with('error', $e->validator->errors()->first());
        }

        if ($photoPath !== null) {
            $attendance->forceFill([$clockingIn ? 'clock_in_photo' : 'clock_out_photo' => $photoPath])->save();
        }

        $zone = $clockingIn ? $attendance->clockInLocation : $attendance->clockOutLocation;
        $offSite = $clockingIn ? $attendance->clock_in_off_site : $attendance->clock_out_off_site;
        $where = $zone ? ' ที่ '.$zone->name : ($offSite ? ' (นอกสถานที่)' : '');

        AuditLogger::log(
            $clockingIn ? 'created' : 'updated',
            ($clockingIn ? 'ลงเวลาเข้างาน' : 'ลงเวลาออกงาน').$where,
            $attendance,
        );

        return redirect()
            ->route('time-clock.show')
            ->with('success', ($clockingIn ? 'ลงเวลาเข้างาน' : 'ลงเวลาออกงาน').'เรียบร้อย'.$where);
    }
}
