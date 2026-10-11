@extends('layouts.app')
@section('title', 'คลังเอกสาร')

@section('content')
    <x-page-header title="คลังเอกสาร" subtitle="เอกสาร นโยบาย และแบบฟอร์มขององค์กร" />

    <div class="grid grid--2">
        <div class="grid content-start">
            <x-card :padding="false">
                @if($documents->isEmpty())
                    <x-empty icon="inbox" title="ยังไม่มีเอกสาร" message="อัปโหลดเอกสารฉบับแรกขององค์กร" />
                @else
                    <div class="table-wrap">
                        <table class="table table--cards">
                            <thead>
                                <tr><th>ชื่อเอกสาร</th><th>หมวด</th><th class="num">ขนาด</th><th class="actions">จัดการ</th></tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $doc)
                                    <tr>
                                        <td class="cell-strong">{{ $doc->title }}<div class="cell-sub">{{ optional($doc->created_at)->translatedFormat('j M Y') }} · {{ $doc->uploader?->name ?? '—' }}</div></td>
                                        <td data-label="หมวด"><x-badge color="blue">{{ $categories[$doc->category] ?? $doc->category }}</x-badge></td>
                                        <td class="num cell-sub" data-label="ขนาด">{{ $doc->size_label }}</td>
                                        <td class="actions">
                                            <div class="flex items-center gap-1 justify-end">
                                                <x-button href="{{ route('documents.download', $doc) }}" variant="ghost" size="sm" icon="download">ดาวน์โหลด</x-button>
                                                @can('documents.manage')
                                                    <form method="POST" action="{{ route('documents.destroy', $doc) }}" data-confirm="ลบเอกสารนี้?">
                                                        @csrf @method('DELETE')
                                                        <x-button type="submit" variant="danger" size="sm" icon="trash">ลบ</x-button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card__pager">{{ $documents->links() }}</div>
                @endif
            </x-card>
        </div>

        <div class="grid content-start">
            @can('documents.manage')
                <x-card title="อัปโหลดเอกสาร">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <x-input name="title" label="ชื่อเอกสาร" :value="old('title')" :required="true" />
                        <div class="field-gap">
                            <x-select name="category" label="หมวดหมู่" :options="$categories" :selected="old('category', 'general')" :required="true" />
                        </div>
                        <div class="field field-gap">
                            <label>ไฟล์เอกสาร <span class="req">*</span></label>
                            <input type="file" name="file" class="input" required>
                            <div class="hint">รองรับ {{ strtoupper(implode(', ', (array) config('hrpro.documents.allowed_types'))) }} — ไม่เกิน {{ (int) config('hrpro.documents.max_upload_mb', 10) }} MB</div>
                            @error('file')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field-gap"><x-button type="submit" icon="plus">อัปโหลด</x-button></div>
                    </form>
                </x-card>
            @endcan
        </div>
    </div>
@endsection
