@props(['title' => null, 'subtitle' => null, 'padding' => true])
<div {{ $attributes->class('card') }}>
    @if($title || isset($actions))
        <div class="card__header">
            <div>
                @if($title)<h3>{{ $title }}</h3>@endif
                @if($subtitle)<div class="cell-sub">{{ $subtitle }}</div>@endif
            </div>
            @isset($actions)<div class="card__actions">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $padding ? 'card__body' : '' }}">{{ $slot }}</div>
    @isset($footer)<div class="card__footer">{{ $footer }}</div>@endisset
</div>
