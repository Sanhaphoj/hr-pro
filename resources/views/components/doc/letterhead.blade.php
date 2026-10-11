@props(['title' => null, 'subtitle' => null, 'meta' => [], 'center' => false])
@php
    $company = config('hrpro.company', []);
    $name = $company['name'] ?? config('hrpro.company_name');
    // The company's own logo; failing that, the system logo when Settings → Logo allows it.
    $logoPath = brand_asset($company['logo'] ?? null) ? $company['logo']
        : (config('hrpro.branding.use_on_documents', true) && brand_asset(config('hrpro.branding.logo')) ? config('hrpro.branding.logo') : null);
    $logo = $logoPath ? doc_src(brand_asset($logoPath), public_path($logoPath)) : null;
    // Without a logo image, the first two letters of the name stand in for one.
    $mark = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(preg_replace('/[^\pL\pN]/u', '', (string) $name), 0, 2));
    $contact = array_filter([
        ! empty($company['tax_id']) ? 'เลขประจำตัวผู้เสียภาษี '.$company['tax_id'] : null,
        ! empty($company['phone']) ? 'โทร. '.$company['phone'] : null,
        $company['email'] ?? null,
    ]);
@endphp
<header {{ $attributes->class(['lh', 'lh--center' => $center]) }}>
    @if($logo)
        <img class="lh__logo lh__logo--img" src="{{ $logo }}" alt="{{ $name }}">
    @else
        <span class="lh__logo" aria-hidden="true">{{ $mark }}</span>
    @endif

    <div class="lh__org">
        <div class="lh__name">{{ $name }}</div>
        @if(! empty($company['name_en']))
            <div class="lh__name-en">{{ $company['name_en'] }}</div>
        @endif
        @if(! empty($company['address']) || $contact)
            <div class="lh__meta">
                @if(! empty($company['address']))<div>{{ $company['address'] }}</div>@endif
                @if($contact)<div>{{ implode(' · ', $contact) }}</div>@endif
            </div>
        @endif
    </div>

    @if($title && ! $center)
        <div class="lh__doc">
            <div class="lh__doc-title">{{ $title }}</div>
            @if($subtitle)<div class="lh__doc-sub">{{ $subtitle }}</div>@endif
            @foreach($meta as $label => $value)
                <div class="lh__doc-meta"><span>{{ $label }}</span> {{ $value }}</div>
            @endforeach
        </div>
    @endif
</header>
