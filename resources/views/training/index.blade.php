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
                <table class="table">
                    <thead>
                        <tr>
                            <th>หลักสูตร</th>
                            <th class="num">ชั่วโมง</th>
                            <th>วันที่</th>
                            <th class="num">ผู้ลงทะเบียน</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td class="cell-strong">{{ $course->title }}</td>
                                <td class="num">{{ $course->hours }}</td>
                                <td class="cell-sub">{{ optional($course->scheduled_date)->translatedFormat('j M Y') ?? '—' }}</td>
                                <td class="num">{{ $course->enrollments_count }}</td>
                                <td style="text-align:right;">
                                    <x-button href="{{ route('training.show', $course) }}" variant="ghost" size="sm" icon="eye">ดู</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;">{{ $courses->links() }}</div>
        @endif
    </x-card>
@endsection
