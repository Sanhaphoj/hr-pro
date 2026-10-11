<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Keeps the front-camera photo taken at a punch.
 *
 * Photos go to the private `local` disk — never the public one, which anyone
 * holding the URL can open — and are only served through
 * AttendancePhotoController, which checks who is asking.
 */
class SelfieStore
{
    private const DISK = 'local';

    private const PREFIX = 'data:image/jpeg;base64,';

    /** Largest photo accepted once decoded. The browser sends ~40–120 KB. */
    public const MAX_BYTES = 1_500_000;

    /**
     * Turn the data URL the browser sends into raw JPEG bytes, or null when it
     * is missing, malformed, too large, or not actually a JPEG.
     */
    public function decode(?string $dataUrl): ?string
    {
        if (! is_string($dataUrl) || ! str_starts_with($dataUrl, self::PREFIX)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen(self::PREFIX)), true);

        if ($binary === false || $binary === '' || strlen($binary) > self::MAX_BYTES) {
            return null;
        }

        // Trust the bytes, not the prefix: they must parse as a real JPEG.
        $info = @getimagesizefromstring($binary);
        if ($info === false || $info[2] !== IMAGETYPE_JPEG || $info[0] > 4000 || $info[1] > 4000) {
            return null;
        }

        return $binary;
    }

    /**
     * @param  'in'|'out'  $action
     */
    public function store(Employee $employee, string $action, string $binary): string
    {
        $now = Carbon::now();

        // Random suffix so a path can never be guessed from employee and time.
        $path = sprintf(
            'attendance-photos/%s/%d-%s-%s-%s.jpg',
            $now->format('Y/m'),
            $employee->id,
            $now->format('Ymd-His'),
            $action,
            Str::lower(Str::random(12)),
        );

        $this->disk()->put($path, $binary);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            $this->disk()->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return (bool) $path && $this->disk()->exists($path);
    }

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
