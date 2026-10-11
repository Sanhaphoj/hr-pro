<!DOCTYPE html>
@php
    // The company's accent is the one colour a document carries. It comes from
    // the environment, so it is checked before it reaches a style attribute.
    $accent = config('hrpro.company.color');
    $accent = is_string($accent) && preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#0e8f88';
    $landscape = trim($__env->yieldContent('orientation')) === 'landscape';
@endphp
<html lang="th" style="--doc-accent: {{ $accent }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    {{-- Browsers offer the title as the file name when saving as PDF. --}}
    <title>@yield('filename', 'เอกสาร')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset_v('css/print.css') }}">
    @include('partials.favicon')
</head>
<body class="doc-desk{{ $landscape ? ' doc-desk--landscape' : '' }}">
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

        <button type="button" class="doc-btn doc-btn--primary" onclick="window.print()" aria-label="พิมพ์หรือบันทึกเป็น PDF">
            <x-icon name="printer" width="18" height="18" /><span>พิมพ์ / บันทึก PDF</span>
        </button>
    </header>

    <p class="doc-tip">
        บันทึกเป็น PDF: กดปุ่มพิมพ์ แล้วเลือกปลายทาง “บันทึกเป็น PDF” · ใช้กระดาษ A4 และปิด “หัวกระดาษและท้ายกระดาษ” ของเบราว์เซอร์เพื่อให้เอกสารเรียบร้อย
    </p>

    <main class="doc-desk__sheets">
        @yield('content')
    </main>
</body>
</html>
