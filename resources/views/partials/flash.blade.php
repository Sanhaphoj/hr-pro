{{-- Confirmations ("saved", "approved") are a toast in the corner: they
     need a glance, not a place in the page. Problems stay inline, next to
     what has to be fixed, until they are dismissed. --}}
<div class="toast-stack" aria-live="polite">
    @foreach (['success' => 'check-circle', 'info' => 'info'] as $type => $icon)
        @if (session($type))
            <div class="toast toast--{{ $type }}" role="status" data-toast>
                <x-icon :name="$icon" />
                <span>{{ session($type) }}</span>
                <button type="button" class="toast__close" aria-label="ปิด" data-toast-close><x-icon name="x" width="15" height="15" /></button>
            </div>
        @endif
    @endforeach
</div>

@if (session('error'))
    <x-alert type="error">{{ session('error') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning">{{ session('warning') }}</x-alert>
@endif

@if ($errors->any())
    <x-alert type="error" :dismiss="false">
        <strong>ไม่สามารถบันทึกข้อมูลได้ — กรุณาตรวจสอบข้อมูลต่อไปนี้:</strong>
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
