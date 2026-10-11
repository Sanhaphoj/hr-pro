<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

if (! function_exists('initials')) {
    /**
     * Build up to two uppercase initials from a full name.
     */
    function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '–';
        }

        $parts = preg_split('/\s+/', $name);
        $first = Str::substr($parts[0] ?? '', 0, 1);
        $second = count($parts) > 1 ? Str::substr(end($parts), 0, 1) : '';

        return Str::upper($first.$second);
    }
}

if (! function_exists('thb')) {
    /**
     * Format a number as Thai Baht currency.
     */
    function thb(int|float|null $amount): string
    {
        return '฿'.number_format((float) ($amount ?? 0), 2);
    }
}

if (! function_exists('avatar_color')) {
    /**
     * Deterministic background colour for an avatar from a seed string.
     */
    function avatar_color(?string $seed): string
    {
        $palette = ['#1e3a5f', '#2563eb', '#7c3aed', '#0f766e', '#b45309', '#be123c', '#0369a1', '#4d7c0f'];

        return $palette[crc32((string) $seed) % count($palette)];
    }
}

if (! function_exists('asset_v')) {
    /**
     * asset() plus a cache-busting stamp from the file's mtime.
     *
     * The bundled CSS/JS are served straight from public/ with no build step
     * and no cache headers, so without this a browser can sit on a stale copy
     * indefinitely and never see a deployed change.
     */
    function asset_v(string $path): string
    {
        $file = public_path($path);
        $stamp = is_file($file) ? filemtime($file) : null;

        return asset($path).($stamp ? '?v='.$stamp : '');
    }
}

/*
|--------------------------------------------------------------------------
| Thai document formatting
|--------------------------------------------------------------------------
| Official paperwork in Thailand writes dates with Thai month names and the
| Buddhist Era year, and states money amounts in words as well as figures.
*/

if (! function_exists('thai_month')) {
    function thai_month(int $month, bool $short = false): string
    {
        $long = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $abbr = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return ($short ? $abbr : $long)[($month - 1) % 12] ?? '';
    }
}

if (! function_exists('thai_day')) {
    /**
     * Day name from Carbon's dayOfWeek (0 = Sunday).
     */
    function thai_day(int $dayOfWeek, bool $short = false): string
    {
        $long = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $abbr = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

        return ($short ? $abbr : $long)[$dayOfWeek % 7];
    }
}

if (! function_exists('thai_date')) {
    /**
     * 13 กันยายน 2569 (long), 13 ก.ย. 69 (short) or 13/09/2569 (numeric).
     */
    function thai_date(DateTimeInterface|string|null $date, string $style = 'long', bool $time = false): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $date = Carbon::parse($date);
        $year = $date->year + 543;

        $text = match ($style) {
            'short' => $date->day.' '.thai_month($date->month, true).' '.substr((string) $year, -2),
            'numeric' => sprintf('%02d/%02d/%d', $date->day, $date->month, $year),
            default => $date->day.' '.thai_month($date->month).' '.$year,
        };

        return $time ? $text.' เวลา '.$date->format('H:i').' น.' : $text;
    }
}

if (! function_exists('thai_number_text')) {
    /**
     * A whole number in Thai words, following the BAHTTEXT conventions:
     * สิบ not หนึ่งสิบ, ยี่สิบ for twenty, and เอ็ด for a trailing one.
     */
    function thai_number_text(int $number): string
    {
        if ($number === 0) {
            return 'ศูนย์';
        }

        $digits = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $places = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;
        $text = $millions > 0 ? thai_number_text($millions).'ล้าน' : '';

        $figures = (string) $rest;
        $length = strlen($figures);

        for ($i = 0; $i < $length && $rest > 0; $i++) {
            $digit = (int) $figures[$i];
            $place = $length - $i - 1;

            if ($digit === 0) {
                continue;
            }

            $text .= match (true) {
                $place === 1 && $digit === 1 => 'สิบ',
                $place === 1 && $digit === 2 => 'ยี่สิบ',
                $place === 0 && $digit === 1 && $number > 9 => 'เอ็ด',
                default => $digits[$digit].$places[$place],
            };
        }

        return $text;
    }
}

if (! function_exists('thai_baht_text')) {
    /**
     * 25750.5 → สองหมื่นห้าพันเจ็ดร้อยห้าสิบบาทห้าสิบสตางค์
     */
    function thai_baht_text(int|float|string|null $amount): string
    {
        $amount = (float) ($amount ?? 0);
        $totalSatang = (int) round(abs($amount) * 100);
        $baht = intdiv($totalSatang, 100);
        $satang = $totalSatang % 100;

        if ($baht === 0 && $satang === 0) {
            return 'ศูนย์บาทถ้วน';
        }

        $text = $baht > 0 ? thai_number_text($baht).'บาท' : '';
        $text .= $satang > 0 ? thai_number_text($satang).'สตางค์' : 'ถ้วน';

        return ($amount < 0 ? 'ลบ' : '').$text;
    }
}

if (! function_exists('thai_duration')) {
    /**
     * Length of service, e.g. "3 ปี 2 เดือน".
     */
    function thai_duration(DateTimeInterface $from, DateTimeInterface $to): string
    {
        $from = Carbon::instance($from)->startOfDay();
        $to = Carbon::instance($to)->startOfDay();

        if ($to->lessThan($from)) {
            return '—';
        }

        $diff = $from->diff($to);
        $parts = array_filter([
            $diff->y ? $diff->y.' ปี' : null,
            $diff->m ? $diff->m.' เดือน' : null,
        ]);

        return $parts ? implode(' ', $parts) : $diff->d.' วัน';
    }
}

if (! function_exists('doc_no')) {
    /**
     * Reference printed on a document, e.g. PAY-256909-000123: a prefix,
     * the Buddhist Era year and month it concerns, and the record id.
     */
    function doc_no(string $prefix, int|string $id, DateTimeInterface|string|null $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();

        return sprintf('%s-%d%02d-%06d', $prefix, $date->year + 543, $date->month, (int) $id);
    }
}

if (! function_exists('format_national_id')) {
    /**
     * 1-2345-67890-12-3. Masked, only the first digit and the last four show,
     * which is enough to tell documents apart without exposing the number.
     */
    function format_national_id(?string $id, bool $masked = false): string
    {
        $digits = preg_replace('/\D/', '', (string) $id);

        if ($digits === '') {
            return '—';
        }

        if (strlen($digits) !== 13) {
            return $masked ? str_repeat('x', strlen($digits)) : (string) $id;
        }

        if ($masked) {
            $digits = $digits[0].'xxxxxxxx'.substr($digits, 9);
        }

        return sprintf('%s-%s-%s-%s-%s',
            $digits[0], substr($digits, 1, 4), substr($digits, 5, 5), substr($digits, 10, 2), $digits[12]);
    }
}

if (! function_exists('brand_asset')) {
    /**
     * URL of an image under public/ (a logo or icon set in Settings), or null
     * when none is set or the file has gone — so callers draw their fallback.
     */
    function brand_asset(?string $path): ?string
    {
        return $path && is_file(public_path($path)) ? asset_v($path) : null;
    }
}
