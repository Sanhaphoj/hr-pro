@extends('layouts.app')
@section('title', 'สรรหาบุคลากร')

@section('content')
    <x-page-header title="สรรหาบุคลากร" subtitle="ประกาศรับสมัครงานและติดตามผู้สมัคร">
        @can('recruitment.manage')
            <x-slot:actions>
                <x-button href="{{ route('recruitment.create') }}" icon="plus">เปิดรับสมัคร</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        @if($postings->isEmpty())
            <x-empty icon="user" title="ยังไม่มีประกาศรับสมัคร" message="เริ่มต้นด้วยการเปิดรับสมัครตำแหน่งแรก" />
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>ตำแหน่ง</th>
                            <th>แผนก</th>
                            <th class="num">อัตรา</th>
                            <th class="num">ผู้สมัคร</th>
                            <th>สถานะ</th>
                            <th class="actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($postings as $posting)
                            <tr>
                                <td class="cell-strong">{{ $posting->title }}</td>
                                <td data-label="แผนก">{{ $posting->department?->name ?? '—' }}</td>
                                <td class="num" data-label="อัตรา">{{ $posting->openings }}</td>
                                <td class="num" data-label="ผู้สมัคร">{{ $posting->candidates_count }}</td>
                                <td data-label="สถานะ"><x-badge :color="$posting->status === 'open' ? 'green' : 'gray'">{{ $posting->status === 'open' ? 'เปิดรับ' : 'ปิดรับ' }}</x-badge></td>
                                <td class="actions">
                                    <x-button href="{{ route('recruitment.show', $posting) }}" variant="ghost" size="sm" icon="eye">ดู</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card__pager">{{ $postings->links() }}</div>
        @endif
    </x-card>
@endsection
