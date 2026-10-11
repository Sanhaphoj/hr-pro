<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\SelfieStore;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the photo taken at a punch. A person's face is personal data, so the
 * file lives on the private disk and this is the only way to read it: the
 * employee it belongs to, or anyone who may see everyone's attendance.
 */
class AttendancePhotoController extends Controller
{
    public function show(Attendance $attendance, string $kind, SelfieStore $selfies): Response
    {
        $user = auth()->user();
        $isOwn = $user->employee !== null && $user->employee->id === $attendance->employee_id;

        abort_unless($isOwn || $user->hasPermission('attendance.viewAll'), 403);

        $path = $kind === 'in' ? $attendance->clock_in_photo : $attendance->clock_out_photo;
        abort_unless($selfies->exists($path), 404);

        return $selfies->disk()->response($path, null, [
            'Content-Type' => 'image/jpeg',
            // Keep it out of shared caches and stop browsers second-guessing the type.
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
