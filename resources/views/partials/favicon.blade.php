{{-- Browser tab icon from Settings → Logo: its own icon, or the system logo. --}}
@php
    $favicon = brand_asset(config('hrpro.branding.favicon')) ?? brand_asset(config('hrpro.branding.logo'));
@endphp
@if($favicon)
    <link rel="icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
@endif
