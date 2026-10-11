{{--
    Full-bleed layout for the time clock. Like the login screen it drops the
    console chrome entirely — no sidebar, no topbar — so the punch screen fills
    the device the way the mobile app it is modelled on does. Navigation lives
    in the slide-in drawer and the bottom tab bar the page draws itself.
--}}
<!DOCTYPE html>
<html lang="th"
      data-theme="{{ auth()->user()?->theme ?? 'teal' }}"
      data-scheme="{{ auth()->user()?->color_scheme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    {{-- Colours the phone status bar to match the screen's gradient. --}}
    <meta name="theme-color" content="#2ec5f0">
    <title>@yield('title', 'ลงเวลา') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    @include('partials.favicon')
    @include('partials.pwa-head')
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
<body class="kiosk">
    @yield('content')

    <script src="{{ asset_v('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
