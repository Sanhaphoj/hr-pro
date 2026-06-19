@if (session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error">{{ session('error') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning">{{ session('warning') }}</x-alert>
@endif

@if (session('info'))
    <x-alert type="info">{{ session('info') }}</x-alert>
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
