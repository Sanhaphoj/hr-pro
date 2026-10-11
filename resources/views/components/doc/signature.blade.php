@props(['role', 'name' => null, 'position' => null, 'date' => null, 'dated' => true])
{{-- A signing block: a dotted line to sign on, the printed name (or a blank
     to write it in), the capacity signed in, and the date. --}}
<div {{ $attributes->class('sign') }}>
    @if($slot->isNotEmpty())
        <div class="sign__note">{{ $slot }}</div>
    @endif
    <div class="sign__line"><span>ลงชื่อ</span></div>
    <div class="sign__name">
        @if($name)
            ( {{ $name }} )
        @else
            ( <span class="sign__blank"></span> )
        @endif
    </div>
    <div class="sign__role">{{ $role }}</div>
    @if($position)
        <div class="sign__meta">{{ $position }}</div>
    @endif
    @if($dated)
        <div class="sign__meta">วันที่ {{ $date ?? '........ / ........ / ........' }}</div>
    @endif
</div>
