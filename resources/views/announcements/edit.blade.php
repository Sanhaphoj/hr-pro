@extends('layouts.app')
@section('title', 'แก้ไขประกาศ')

@section('content')
    <x-page-header title="แก้ไขประกาศ" subtitle="ปรับปรุงเนื้อหาประกาศ">
        <x-slot:actions>
            <x-button href="{{ route('announcements.show', $announcement) }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('announcements.update', $announcement) }}">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="col-span-2">
                    <x-input name="title" label="หัวข้อประกาศ" :value="$announcement->title" :required="true" />
                </div>

                <x-select
                    name="category"
                    label="หมวดหมู่"
                    :options="$categories"
                    :selected="$announcement->category"
                    :required="true"
                    placeholder="เลือกหมวดหมู่" />

                <x-input name="published_at" type="datetime-local" label="วันที่เผยแพร่"
                         :value="optional($announcement->published_at)->format('Y-m-d\TH:i')"
                         hint="เว้นว่างเพื่อเผยแพร่ทันทีเมื่อเปิดใช้งาน" />

                <div class="col-span-2">
                    <x-textarea name="body" label="เนื้อหา" :value="$announcement->body" :rows="8" :required="true" />
                </div>

                <x-checkbox name="is_published" label="เผยแพร่ประกาศนี้" :checked="$announcement->is_published" />
                <x-checkbox name="pinned" label="ปักหมุดไว้ด้านบน" :checked="$announcement->pinned" />
            </div>

            <div class="flex items-center gap-2" style="margin-top:18px;">
                <x-button type="submit" icon="check">บันทึกการแก้ไข</x-button>
                <x-button href="{{ route('announcements.show', $announcement) }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
