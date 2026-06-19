@props(['icon' => 'inbox', 'title' => 'ยังไม่มีข้อมูล', 'message' => null])
<div class="empty">
    <x-icon :name="$icon" width="46" height="46" />
    <h4>{{ $title }}</h4>
    @if($message)<p>{{ $message }}</p>@endif
    {{ $slot }}
</div>
