@extends('layouts.app')
@section('title', 'คลังเอกสาร')

@section('content')
    <x-page-header title="คลังเอกสาร" subtitle="เอกสาร นโยบาย และแบบฟอร์มขององค์กร" />

    <div class="grid grid--2">
        <div class="grid" style="align-content:start;">
            <x-card :padding="false">
                @if($documents->isEmpty())
                    <x-empty icon="inbox" title="ยังไม่มีเอกสาร" message="อัปโหลดเอกสารฉบับแรกขององค์กร" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr><th>ชื่อเอกสาร</th><th>หมวด</th><th class="num">ขนาด</th><th style="text-align:right;">จัดการ</th></tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $doc)
                                    <tr>
                                        <td class="cell-strong">{{ $doc->title }}<div class="cell-sub">{{ optional($doc->created_at)->translatedFormat('j M Y') }} · {{ $doc->uploader?->name ?? '—' }}</div></td>
                                        <td><x-badge color="blue">{{ $categories[$doc->category] ?? $doc->category }}</x-badge></td>
                                        <td class="num cell-sub">{{ $doc->size_label }}</td>
                                        <td style="text-align:right;">
                                            <div class="flex items-center gap-1" style="justify-content:flex-end;">
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
                    <div style="padding:12px 20px;">{{ $documents->links() }}</div>
                @endif
            </x-card>
        </div>

        <div class="grid" style="align-content:start;">
            @can('documents.manage')
                <x-card title="อัปโหลดเอกสาร">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <x-input name="title" label="ชื่อเอกสาร" :value="old('title')" :required="true" />
                        <div style="margin-top:12px;">
                            <x-select name="category" label="หมวดหมู่" :options="$categories" :selected="old('category', 'general')" :required="true" />
                        </div>
                        <div class="field" style="margin-top:12px;">
                            <label>ไฟล์เอกสาร <span class="req">*</span></label>
                            <input type="file" name="file" class="input" required>
                            <div class="hint">รองรับ PDF, Word, Excel, รูปภาพ — ไม่เกิน 10 MB</div>
                            @error('file')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        <div style="margin-top:14px;"><x-button type="submit" icon="plus">อัปโหลด</x-button></div>
                    </form>
                </x-card>
            @endcan
        </div>
    </div>
@endsection
