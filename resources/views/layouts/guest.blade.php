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
        <div class="auth__wrap">
            <div class="auth__card">
                <div class="auth__head">
                    <div class="auth__brand">
                        <span class="logo">HR</span>
                        <div>
                            <b>HR PRO</b>
                            <span class="auth__co">{{ config('hrpro.company_name') }}</span>
                        </div>
                    </div>
                    <div class="auth__tag">ระบบบริหารทรัพยากรบุคคลสำหรับองค์กรมืออาชีพ</div>
                </div>
                <div class="auth__body">
                    @include('partials.flash')
                    @yield('content')
                </div>
            </div>
            <div class="auth__foot">
                <x-icon name="lock" /> การเชื่อมต่อปลอดภัย &middot; &copy; {{ date('Y') }} {{ config('hrpro.company_name') }}
            </div>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
