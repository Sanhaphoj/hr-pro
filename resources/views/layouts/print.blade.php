<!DOCTYPE html>
@php
    // The company's accent is the one colour a document carries. It comes from
    // the environment, so it is checked before it reaches a style attribute.
    $accent = config('hrpro.company.color');
    $accent = is_string($accent) && preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#0e8f88';
    $landscape = trim($__env->yieldContent('orientation')) === 'landscape';
    // Being printed to a PDF file on the server (App\Http\Middleware\RenderPdf):
    // no toolbar, and the stylesheet is inlined since Chrome reads a local copy.
    $pdf = (bool) ($pdfMode ?? false);
    $canDownload = ! $pdf && \App\Services\PdfRenderer::available();
@endphp
<html lang="th" style="--doc-accent: {{ $accent }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    {{-- Browsers offer the title as the file name when saving as PDF. --}}
    <title>@yield('filename', 'เอกสาร')</title>
    {{-- Sarabun ships with the app (public/fonts/sarabun, SIL OFL), so a
         document looks the same offline and in a server-made PDF. There the
         files are embedded, since Chrome reads a local copy of the page. --}}
    <style>
        @foreach([400 => 'Regular', 500 => 'Medium', 600 => 'SemiBold', 700 => 'Bold'] as $weight => $face)
        @font-face {
            font-family: "Sarabun"; font-style: normal; font-weight: {{ $weight }}; font-display: swap;
            src: url("{{ $pdf
                ? 'data:font/ttf;base64,'.base64_encode(file_get_contents(public_path("fonts/sarabun/Sarabun-{$face}.ttf")))
                : asset("fonts/sarabun/Sarabun-{$face}.ttf") }}") format("truetype");
        }
        @endforeach
    </style>
    @if($pdf)
        <style>{!! file_get_contents(public_path('css/print.css')) !!}</style>
    @else
        <link rel="stylesheet" href="{{ asset_v('css/print.css') }}">
        @include('partials.favicon')
    @endif
</head>
<body class="doc-desk{{ $landscape ? ' doc-desk--landscape' : '' }}{{ $pdf ? ' doc-desk--pdf' : '' }}">
    @unless($pdf)
    <header class="doc-bar">
        <a class="doc-bar__back" href="@yield('back', route('dashboard'))">
            <x-icon name="arrow-left" width="18" height="18" /><span>กลับ</span>
        </a>

        <div class="doc-bar__title">
            <b>@yield('title')</b>
            <small>ตัวอย่างก่อนพิมพ์ · กระดาษ A4 {{ $landscape ? 'แนวนอน' : 'แนวตั้ง' }}</small>
        </div>

        @hasSection('options')
            <div class="doc-bar__options">@yield('options')</div>
        @endif

        <div class="doc-bar__actions">
        @if($canDownload)
            {{-- Same URL with ?download=pdf: the server prints it to a file. --}}
            <a class="doc-btn" href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}" data-pdf-download aria-label="ดาวน์โหลดไฟล์ PDF">
                <x-icon name="download" width="18" height="18" /><span>ดาวน์โหลด PDF</span>
            </a>
        @endif
        <button type="button" class="doc-btn doc-btn--primary" onclick="window.print()" aria-label="พิมพ์หรือบันทึกเป็น PDF">
            <x-icon name="printer" width="18" height="18" /><span>พิมพ์ / บันทึก PDF</span>
        </button>
        </div>
    </header>

    @if(session('pdf_error'))
        <p class="doc-tip doc-tip--error" role="alert">{{ session('pdf_error') }}</p>
    @else
        <p class="doc-tip">
            @if($canDownload)
                กด “ดาวน์โหลด PDF” เพื่อรับไฟล์ทันที หรือกดพิมพ์เพื่อพิมพ์ลงกระดาษ A4
            @else
                บันทึกเป็น PDF: กดปุ่มพิมพ์ แล้วเลือกปลายทาง “บันทึกเป็น PDF” · ใช้กระดาษ A4 และปิด “หัวกระดาษและท้ายกระดาษ” ของเบราว์เซอร์เพื่อให้เอกสารเรียบร้อย
            @endif
        </p>
    @endif
    @endunless

    <main class="doc-desk__sheets">
        @yield('content')
    </main>

    @if($canDownload)
        <script>
            // Making the file takes a few seconds; say so instead of looking stuck.
            document.querySelectorAll('[data-pdf-download]').forEach(function (link) {
                link.addEventListener('click', function () {
                    var label = link.querySelector('span');
                    var text = label.textContent;
                    link.classList.add('is-busy');
                    label.textContent = 'กำลังสร้างไฟล์…';
                    setTimeout(function () { link.classList.remove('is-busy'); label.textContent = text; }, 6000);
                });
            });
        </script>
    @endif
</body>
</html>
