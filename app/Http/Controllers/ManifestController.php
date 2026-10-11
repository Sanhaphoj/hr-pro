<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Web app manifest, so phones can install HR PRO to the home screen and
 * open it full-screen like a native app. Built per request because the
 * name comes from settings; the shortcuts jump straight to the punch clock
 * and the leave form from a long-press on the icon.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $name = config('app.name');

        return response()->json([
            'name' => $name.' · '.config('hrpro.company.name', config('hrpro.company_name')),
            'short_name' => $name,
            'description' => 'ระบบบริหารทรัพยากรบุคคล — ลงเวลา ยื่นใบลา ดูสลิป ได้จากมือถือ',
            'lang' => 'th',
            'id' => '/',
            'start_url' => '/?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#f2f6f6',
            'theme_color' => '#0d2b33',
            'icons' => [
                ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'ลงเวลาเข้า–ออกงาน', 'short_name' => 'ลงเวลา', 'url' => '/time-clock',
                    'icons' => [['src' => asset('icons/icon-192.png'), 'sizes' => '192x192']]],
                ['name' => 'ยื่นใบลา', 'short_name' => 'ยื่นใบลา', 'url' => '/leave-requests/create',
                    'icons' => [['src' => asset('icons/icon-192.png'), 'sizes' => '192x192']]],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
