@extends('layouts.app')
@section('title', $posting->title)

@section('content')
    <x-page-header :title="$posting->title" :subtitle="($posting->department?->name ?? 'ทุกแผนก').' · '.$posting->openings.' อัตรา'">
        <x-slot:breadcrumb><a href="{{ route('recruitment.index') }}">← กลับรายการสรรหา</a></x-slot:breadcrumb>
        @can('recruitment.manage')
            <x-slot:actions>
                <form method="POST" action="{{ route('recruitment.destroy', $posting) }}" data-confirm="ลบประกาศนี้?">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm" icon="trash">ลบประกาศ</x-button>
                </form>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <div class="grid grid--2">
        <div class="grid" style="align-content:start;">
            <x-card title="ผู้สมัคร ({{ $posting->candidates->count() }})" :padding="false">
                @if($posting->candidates->isEmpty())
                    <x-empty icon="user" title="ยังไม่มีผู้สมัคร" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>ชื่อ</th><th>ติดต่อ</th><th>สถานะ</th></tr></thead>
                            <tbody>
                                @foreach($posting->candidates as $candidate)
                                    <tr>
                                        <td class="cell-strong">{{ $candidate->name }}</td>
                                        <td class="cell-sub">{{ $candidate->email ?: $candidate->phone ?: '—' }}</td>
                                        <td>
                                            @can('recruitment.manage')
                                                <form method="POST" action="{{ route('recruitment.candidates.update', [$posting, $candidate]) }}" class="flex items-center gap-1">
                                                    @csrf @method('PUT')
                                                    <select name="stage" class="input" style="max-width:150px;" onchange="this.form.submit()">
                                                        @foreach($stages as $value => $label)
                                                            <option value="{{ $value }}" @selected($candidate->stage === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @else
                                                <x-badge color="blue">{{ $stages[$candidate->stage] ?? $candidate->stage }}</x-badge>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="grid" style="align-content:start;">
            @if($posting->description)
                <x-card title="รายละเอียดงาน">
                    <p style="white-space:pre-line; margin:0;">{{ $posting->description }}</p>
                </x-card>
            @endif

            @can('recruitment.manage')
                <x-card title="เพิ่มผู้สมัคร">
                    <form method="POST" action="{{ route('recruitment.candidates.store', $posting) }}">
                        @csrf
                        <x-input name="name" label="ชื่อ-นามสกุล" :required="true" />
                        <div style="margin-top:12px;"><x-input name="email" type="email" label="อีเมล" /></div>
                        <div style="margin-top:12px;"><x-input name="phone" label="โทรศัพท์" /></div>
                        <div style="margin-top:12px;"><x-textarea name="note" label="บันทึก" rows="2" /></div>
                        <div style="margin-top:14px;"><x-button type="submit" icon="plus">เพิ่มผู้สมัคร</x-button></div>
                    </form>
                </x-card>
            @endcan
        </div>
    </div>
@endsection
