@extends('layouts.app')
@section('title', 'แก้ไขบริเวณลงเวลา')

@section('content')
    <x-page-header title="แก้ไขบริเวณลงเวลา" :subtitle="$location->name">
        <x-slot:actions>
            <x-button href="{{ route('work-locations.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลบริเวณ">
        <form method="POST" action="{{ route('work-locations.update', $location) }}">
            @csrf
            @method('PUT')
            @include('work-locations._form', ['location' => $location])

            <div class="form-actions">
                <x-button type="submit" icon="check">บันทึก</x-button>
                <x-button href="{{ route('work-locations.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
