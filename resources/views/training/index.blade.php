@extends('layouts.app')
@section('title', 'ฝึกอบรม')

@section('content')
    <x-page-header title="หลักสูตรฝึกอบรม" subtitle="จัดการหลักสูตรและการลงทะเบียนของพนักงาน">
        @can('training.manage')
            <x-slot:actions>
                <x-button href="{{ route('training.create') }}" icon="plus">เพิ่มหลักสูตร</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <x-card :padding="false">
        @if($courses->isEmpty())
            <x-empty icon="briefcase" title="ยังไม่มีหลักสูตร" message="เริ่มต้นด้วยการเพิ่มหลักสูตรฝึกอบรมแรก" />
        @else
            <div class="table-wrap">
                <table class="table table--cards">
                    <thead>
                        <tr>
                            <th>หลักสูตร</th>
                            <th class="num">ชั่วโมง</th>
                            <th>วันที่</th>
                            <th class="num">ผู้ลงทะเบียน</th>
                            <th class="actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td class="cell-strong">{{ $course->title }}</td>
                                <td class="num" data-label="ชั่วโมง">{{ $course->hours }}</td>
                                <td class="cell-sub" data-label="วันที่">{{ optional($course->scheduled_date)->translatedFormat('j M Y') ?? '—' }}</td>
                                <td class="num" data-label="ผู้ลงทะเบียน">{{ $course->enrollments_count }}</td>
                                <td class="actions">
                                    <x-button href="{{ route('training.show', $course) }}" variant="ghost" size="sm" icon="eye">ดู</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card__pager">{{ $courses->links() }}</div>
        @endif
    </x-card>
@endsection
