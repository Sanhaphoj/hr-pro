@extends('layouts.app')
@section('title', 'เริ่มงาน (Onboarding)')

@section('content')
    <x-page-header title="เริ่มงาน (Onboarding)" subtitle="เช็กลิสต์สำหรับการเริ่มงานของคุณ" />

    @if(! $employee)
        <x-card>
            <x-empty icon="user" title="ยังไม่มีโปรไฟล์พนักงาน"
                message="บัญชีนี้ยังไม่ได้ผูกกับข้อมูลพนักงาน กรุณาติดต่อฝ่ายบุคคล" />
        </x-card>
    @else
        <div style="max-width:720px;">
            <x-card :title="'ความคืบหน้า '.$progress.'%'">
                <div class="progress"><span style="width: {{ $progress }}%"></span></div>
                <p class="cell-sub" style="margin-top:8px;">ทำเสร็จแล้ว {{ $done }}/{{ $tasks->count() }} รายการ</p>
            </x-card>

            <x-card title="รายการที่ต้องทำ" :padding="false" class="mt-2">
                <ul class="divide-list">
                    @foreach($tasks as $task)
                        <li class="flex items-center gap-2">
                            <form method="POST" action="{{ route('onboarding.toggle', $task) }}">
                                @csrf
                                <button type="submit" class="onboard-check {{ $task->is_done ? 'is-done' : '' }}" aria-label="สลับสถานะ">
                                    @if($task->is_done)<x-icon name="check" width="15" height="15" />@endif
                                </button>
                            </form>
                            <div style="flex:1;">
                                <div class="cell-strong {{ $task->is_done ? 'onboard-done' : '' }}">{{ $task->title }}</div>
                                <div class="cell-sub">{{ $task->description }}</div>
                            </div>
                            @if($task->is_done)
                                <span class="cell-sub">{{ optional($task->completed_at)->translatedFormat('j M') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    @endif
@endsection
