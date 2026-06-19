<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'HR PRO') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="background:var(--c-bg);">
    <div style="min-height:100vh; display:grid; place-items:center; padding:24px;">
        <div class="card" style="max-width:460px; width:100%; text-align:center;">
            <div class="card__body" style="padding:44px 32px;">
                <div style="font-size:64px; font-weight:800; color:var(--c-primary); line-height:1;">@yield('code')</div>
                <h1 style="font-size:20px; margin:14px 0 8px;">@yield('heading')</h1>
                <p class="text-soft" style="margin:0 0 24px;">@yield('message')</p>
                <a href="{{ url('/') }}" class="btn btn--primary">กลับสู่หน้าหลัก</a>
            </div>
        </div>
    </div>
</body>
</html>
