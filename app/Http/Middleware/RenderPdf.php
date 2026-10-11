<?php

namespace App\Http\Middleware;

use App\Services\PdfRenderer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * "ดาวน์โหลด PDF" for every printable document. The same URL with
 * ?download=pdf runs the same controller — so the same permission checks
 * and audit entry — and the HTML it returns is printed to a PDF file named
 * after the document.
 */
class RenderPdf
{
    public function __construct(private readonly PdfRenderer $renderer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query('download') !== 'pdf') {
            return $next($request);
        }

        abort_unless(PdfRenderer::available(), 404);

        // The print layout drops its toolbar and inlines its stylesheet.
        View::share('pdfMode', true);

        $response = $next($request);

        if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $html = (string) $response->getContent();

        try {
            $pdf = $this->renderer->render($html);
        } catch (Throwable $e) {
            report($e);

            return redirect()->to($request->fullUrlWithoutQuery('download'))
                ->with('pdf_error', 'สร้างไฟล์ PDF ไม่สำเร็จ กรุณาใช้ปุ่มพิมพ์แล้วเลือก “บันทึกเป็น PDF” แทน');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $this->disposition($this->filename($html)),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** The document's own name, as the browser would offer it from <title>. */
    private function filename(string $html): string
    {
        $title = preg_match('/<title>(.*?)<\/title>/su', $html, $m) ? html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'เอกสาร';
        // Characters no operating system allows in a file name.
        $title = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $title)) ?: 'เอกสาร';

        return $title.'.pdf';
    }

    /** Thai names need RFC 5987 encoding; the plain name is an ASCII fallback. */
    private function disposition(string $name): string
    {
        // Old clients that ignore filename* get the ASCII part, e.g. "EMP-0001_2569-05.pdf".
        $ascii = trim((string) preg_replace('/[^\x20-\x7E]+/', '', $name), '_- ');
        $ascii = $ascii === '' || $ascii === '.pdf' ? 'document.pdf' : $ascii;

        return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', str_replace('"', '', $ascii), rawurlencode($name));
    }
}
