<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns a printable document's HTML into a PDF with headless Chrome — the
 * same engine that draws the on-screen preview, so the file matches it
 * exactly, Thai shaping and line breaking included. A4 size, margins and
 * page numbers come from the @page rules in print.css.
 *
 * Chrome is found from HRPRO_CHROME_PATH or the usual install locations.
 * Without it, the download button is not offered and printing through the
 * browser still works.
 */
class PdfRenderer
{
    private const CANDIDATES = [
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ];

    public static function binary(): ?string
    {
        $configured = config('hrpro.pdf.chrome_path');
        if ($configured) {
            return is_file($configured) ? $configured : null;
        }

        foreach (self::CANDIDATES as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function available(): bool
    {
        return (bool) config('hrpro.pdf.enabled', true) && self::binary() !== null;
    }

    /**
     * @return string the PDF's bytes
     */
    public function render(string $html): string
    {
        $binary = self::binary() ?? throw new RuntimeException('Chrome was not found for PDF rendering.');

        $dir = storage_path('app/pdf-tmp');
        File::ensureDirectoryExists($dir);
        $id = (string) Str::uuid();
        $source = "{$dir}/{$id}.html";
        $target = "{$dir}/{$id}.pdf";
        // Each run gets its own throwaway profile so concurrent runs never share state.
        $profile = "{$dir}/{$id}-profile";

        File::put($source, $html);

        try {
            // One Chrome at a time: a small server has room for one, not five.
            return Cache::lock('pdf-render', 90)->block(45, function () use ($binary, $source, $target, $profile) {
                $result = Process::timeout(60)->run([
                    $binary,
                    '--headless=new',
                    '--disable-gpu',
                    '--no-sandbox',              // the container runs as root
                    '--disable-dev-shm-usage',   // small /dev/shm on hosted containers
                    '--no-first-run',
                    '--no-default-browser-check',
                    '--hide-scrollbars',
                    '--user-data-dir='.$profile,
                    '--no-pdf-header-footer',
                    '--run-all-compositor-stages-before-draw',
                    // Fonts and images are embedded; this only lets layout settle.
                    '--virtual-time-budget=3000',
                    '--print-to-pdf='.$target,
                    'file:///'.ltrim(str_replace('\\', '/', $source), '/'),
                ]);

                if (! is_file($target) || filesize($target) === 0) {
                    Log::error('PDF rendering failed', ['exit' => $result->exitCode(), 'error' => Str::limit($result->errorOutput(), 2000)]);

                    throw new RuntimeException('Chrome did not produce a PDF.');
                }

                return File::get($target);
            });
        } finally {
            File::delete([$source, $target]);
            File::deleteDirectory($profile);
        }
    }
}
