<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'เข้าสู่ระบบ') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="auth">
        <div class="auth__aside">
            <h2>ระบบบริหารทรัพยากรบุคคล<br>สำหรับองค์กรยุคใหม่</h2>
            <p>จัดการข้อมูลพนักงาน การลา การลงเวลา และการอนุมัติ ได้ในที่เดียว อย่างปลอดภัยและเป็นมืออาชีพ</p>
            <ul>
                <li><x-icon name="users" /> ฐานข้อมูลพนักงานแบบรวมศูนย์</li>
                <li><x-icon name="calendar" /> ระบบลางานพร้อมสายอนุมัติ</li>
                <li><x-icon name="clock" /> ลงเวลาเข้า–ออกงานอัตโนมัติ</li>
                <li><x-icon name="shield" /> สิทธิ์การเข้าถึงตามบทบาท (RBAC)</li>
            </ul>
        </div>
        <div class="auth__form">
            <div class="auth__card">
                <div class="logo-row">
                    <span class="logo">HR</span>
                    <div>
                        <b style="font-size:18px;">HR PRO</b>
                        <div class="cell-sub">{{ config('hrpro.company_name') }}</div>
                    </div>
                </div>
                @include('partials.flash')
                @yield('content')
            </div>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
