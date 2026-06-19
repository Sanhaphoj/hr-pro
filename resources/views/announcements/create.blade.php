@extends('layouts.app')
@section('title', 'เขียนประกาศ')

@section('content')
    <x-page-header title="เขียนประกาศ" subtitle="สร้างประกาศใหม่สำหรับองค์กร">
        <x-slot:actions>
            <x-button href="{{ route('announcements.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('announcements.store') }}">
            @csrf

            <div class="form-grid">
                <div class="col-span-2">
                    <x-input name="title" label="หัวข้อประกาศ" :required="true" />
                </div>

                <x-select
                    name="category"
                    label="หมวดหมู่"
                    :options="$categories"
                    :required="true"
                    placeholder="เลือกหมวดหมู่" />

                <x-input name="published_at" type="datetime-local" label="วันที่เผยแพร่"
                         hint="เว้นว่างเพื่อเผยแพร่ทันทีเมื่อเปิดใช้งาน" />

                <div class="col-span-2">
                    <x-textarea name="body" label="เนื้อหา" :rows="8" :required="true" />
                </div>

                <x-checkbox name="is_published" label="เผยแพร่ประกาศนี้" />
                <x-checkbox name="pinned" label="ปักหมุดไว้ด้านบน" />
            </div>

            <div class="flex items-center gap-2" style="margin-top:18px;">
                <x-button type="submit" icon="check">บันทึกประกาศ</x-button>
                <x-button href="{{ route('announcements.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
