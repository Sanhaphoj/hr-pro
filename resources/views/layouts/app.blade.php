<!DOCTYPE html>
<html lang="th"
      data-theme="{{ auth()->user()?->theme ?? 'teal' }}"
      data-scheme="{{ auth()->user()?->color_scheme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'แดชบอร์ด') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    @stack('head')
</head>
<body>
    <div class="app">
        @include('partials.sidebar')

        <div class="main">
            @include('partials.topbar')

            <main class="content">
                {{-- Paper only. The sidebar and top bar don't print, so this
                     says whose system the page came from and when. --}}
                <div class="print-head" aria-hidden="true">
                    <b>{{ config('hrpro.company.name', config('hrpro.company_name')) }}</b>
                    <span>@yield('title', 'แดชบอร์ด') · พิมพ์เมื่อ {{ thai_date(now(), 'long', true) }}</span>
                </div>
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.command-palette')

    <script src="{{ asset_v('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
