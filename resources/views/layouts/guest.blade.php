<!DOCTYPE html>
<html lang="th"
      data-theme="{{ auth()->user()?->theme ?? config('hrpro.appearance.theme', 'teal') }}"
      data-scheme="{{ auth()->user()?->color_scheme ?? config('hrpro.appearance.color_scheme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d2b33">
    <title>@yield('title', 'เข้าสู่ระบบ') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    @include('partials.favicon')
    {{-- Appearance is applied before first paint. `auto` resolves against the
         operating system, and the rail state is restored here too, so nothing
         flashes the wrong colours on load. --}}
    <script>
        (function () {
            var el = document.documentElement;
            var scheme = el.dataset.scheme;
            if (scheme === 'auto') {
                el.dataset.scheme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                    el.dataset.scheme = e.matches ? 'dark' : 'light';
                });
            }
            try { if (localStorage.getItem('hrpro.rail') === '1') el.classList.add('is-rail'); } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="auth">
        <div class="auth__panel">
            {{-- Brand half: what the product is, for anyone landing here cold. --}}
            <aside class="auth__pane">
                <div class="auth__brand">
                    @include('partials.brand-mark')
                    <div>
                        <b>{{ config('app.name') }}</b>
                        <span class="auth__co">{{ config('hrpro.company_name') }}</span>
                    </div>
                </div>

                <div class="auth__pitch">
                    <h2>ระบบบริหารทรัพยากรบุคคล ครบทุกงานในที่เดียว</h2>

                    <ul class="auth__points">
                        <li><x-icon name="map-pin" />ลงเวลาเข้า–ออกงานพร้อมตรวจพิกัด GPS ตามบริเวณที่กำหนด</li>
                        <li><x-icon name="calendar" />ยื่นลาและอนุมัติครบวงจร ตัดยอดวันลาอัตโนมัติ</li>
                        <li><x-icon name="shield" />กำหนดสิทธิ์รายบทบาท พร้อมบันทึกการใช้งานย้อนหลัง</li>
                    </ul>
                </div>

                <div class="auth__legal">&copy; {{ date('Y') }} {{ config('hrpro.company_name') }}</div>
            </aside>

            <main class="auth__form">
                <div class="auth__card">
                    @include('partials.flash')
                    @yield('content')

                    <div class="auth__foot">
                        <x-icon name="lock" /> การเชื่อมต่อเข้ารหัสแบบปลอดภัย
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="{{ asset_v('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
