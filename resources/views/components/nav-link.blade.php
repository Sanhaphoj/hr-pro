@props(['href', 'icon', 'pattern'])
<a href="{{ $href }}" class="nav__link {{ request()->routeIs($pattern) ? 'is-active' : '' }}">
    <x-icon :name="$icon" />
    <span>{{ $slot }}</span>
</a>
