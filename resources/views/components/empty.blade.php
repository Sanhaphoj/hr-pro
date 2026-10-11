@props(['icon' => 'inbox', 'title' => 'ยังไม่มีข้อมูล', 'message' => null])
<div class="empty">
    <span class="empty__icon"><x-icon :name="$icon" /></span>
    <h4>{{ $title }}</h4>
    @if($message)<p>{{ $message }}</p>@endif
    {{ $slot }}
</div>
