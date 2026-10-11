@props(['note' => null])
<footer class="doc-foot">
    <span>{{ $note ?? 'เอกสารนี้จัดทำจากระบบ '.config('app.name') }}</span>
    <span>พิมพ์เมื่อ {{ thai_date(now(), 'long', true) }}@auth · โดย {{ auth()->user()->name }}@endauth</span>
</footer>
