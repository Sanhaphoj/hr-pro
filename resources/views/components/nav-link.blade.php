@props(['href', 'icon', 'pattern'])
{{-- data-label feeds the tooltip that stands in for the text when the
     sidebar is collapsed to an icon rail. --}}
<a href="{{ $href }}"
   class="nav__link {{ request()->routeIs(...explode('|', $pattern)) ? 'is-active' : '' }}"
   data-label="{{ trim(strip_tags($slot)) }}">
    <x-icon :name="$icon" />
    <span>{{ $slot }}</span>
</a>
