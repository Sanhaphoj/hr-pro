{{-- Installable app: manifest, the colour the phone paints its status bar,
     and the home-screen icon iOS reads (it ignores the manifest's icons). --}}
<link rel="manifest" href="{{ route('manifest') }}">
<meta name="theme-color" content="#0d2b33">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
@unless(config('hrpro.branding.favicon') || config('hrpro.branding.logo'))
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
@endunless
