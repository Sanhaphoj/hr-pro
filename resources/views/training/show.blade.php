@extends('layouts.app')
@section('title', $course->title)

@section('content')
    <x-page-header :title="$course->title" :subtitle="$course->hours.' ชั่วโมง'.($course->scheduled_date ? ' · '.$course->scheduled_date->translatedFormat('j F Y') : '')">
        <x-slot:breadcrumb><a href="{{ route('training.index') }}">← กลับรายการหลักสูตร</a></x-slot:breadcrumb>
        @can('training.manage')
            <x-slot:actions>
                <form method="POST" action="{{ route('training.destroy', $course) }}" data-confirm="ลบหลักสูตรนี้?">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm" icon="trash">ลบหลักสูตร</x-button>
                </form>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <div class="grid grid--2">
        <div class="grid" style="align-content:start;">
            <x-card title="ผู้ลงทะเบียน ({{ $course->enrollments->count() }})" :padding="false">
                @if($course->enrollments->isEmpty())
                    <x-empty icon="users" title="ยังไม่มีผู้ลงทะเบียน" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>พนักงาน</th><th>สถานะ</th><th></th></tr></thead>
                            <tbody>
                                @foreach($course->enrollments as $enrollment)
                                    <tr>
                                        <td class="cell-strong">{{ $enrollment->employee?->full_name ?? '—' }}</td>
                                        <td><x-badge :color="$enrollment->status === 'completed' ? 'green' : 'blue'">{{ $enrollment->status === 'completed' ? 'จบหลักสูตร' : 'กำลังอบรม' }}</x-badge></td>
                                        <td style="text-align:right;">
                                            @can('training.manage')
                                                @if($enrollment->status !== 'completed')
                                                    <form method="POST" action="{{ route('training.complete', [$course, $enrollment]) }}">
                                                        @csrf
                                                        <x-button type="submit" variant="ghost" size="sm" icon="check">จบแล้ว</x-button>
                                                    </form>
                                                @endif
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
            @if($course->description)
                <x-card title="รายละเอียดหลักสูตร">
                    <p style="white-space:pre-line; margin:0;">{{ $course->description }}</p>
                </x-card>
            @endif

            @can('training.manage')
                <x-card title="ลงทะเบียนผู้เข้าอบรม">
                    @if($employees->isEmpty())
                        <p class="cell-sub" style="margin:0;">พนักงานทุกคนลงทะเบียนแล้ว</p>
                    @else
                        <form method="POST" action="{{ route('training.enroll', $course) }}">
                            @csrf
                            <x-select name="employee_id" label="เลือกพนักงาน" :options="$employees" placeholder="— เลือกพนักงาน —" :required="true" />
                            <div style="margin-top:14px;"><x-button type="submit" icon="plus">ลงทะเบียน</x-button></div>
                        </form>
                    @endif
                </x-card>
            @endcan
        </div>
    </div>
@endsection
