@extends('layouts.app')
@section('title', 'เพิ่มบริเวณลงเวลา')

@section('content')
    <x-page-header title="เพิ่มบริเวณลงเวลา" subtitle="กำหนดจุดศูนย์กลางและรัศมีที่พนักงานลงเวลาได้">
        <x-slot:actions>
            <x-button href="{{ route('work-locations.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลบริเวณ">
        <form method="POST" action="{{ route('work-locations.store') }}">
            @csrf
            @include('work-locations._form', ['location' => null])

            <div class="form-actions">
                <x-button type="submit" icon="check">บันทึก</x-button>
                <x-button href="{{ route('work-locations.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
