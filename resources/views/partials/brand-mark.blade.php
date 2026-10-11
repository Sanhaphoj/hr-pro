{{-- The system logo from Settings → Logo, or the "HR" monogram without one.
     $base is the class the surrounding layout styles the mark with. --}}
@php
    $brandLogo = brand_asset(config('hrpro.branding.logo'));
    $base = $base ?? 'logo';
@endphp
@if($brandLogo)
    <img class="{{ $base }} logo--img" src="{{ $brandLogo }}" alt="{{ config('app.name') }}">
@else
    <span class="{{ $base }}" aria-hidden="true">HR</span>
@endif
