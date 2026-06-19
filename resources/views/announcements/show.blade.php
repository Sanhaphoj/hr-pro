@extends('layouts.app')
@section('title', $announcement->title)

@section('content')
    <x-page-header :title="$announcement->title" subtitle="รายละเอียดประกาศ">
        <x-slot:actions>
            <x-button href="{{ route('announcements.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
            @can('announcements.manage')
                <x-button href="{{ route('announcements.edit', $announcement) }}" variant="secondary" icon="edit">แก้ไข</x-button>
                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" data-confirm="ยืนยันการลบ?">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash">ลบ</x-button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <div class="flex items-center gap-2" style="margin-bottom:14px; flex-wrap:wrap;">
            @if($announcement->pinned)<x-icon name="pin" width="16" height="16" />@endif
            <x-status-badge type="announcement" :value="$announcement->category" />
            @unless($announcement->is_published)
                <x-badge color="gray" :dot="true">ฉบับร่าง</x-badge>
            @endunless
        </div>

        <dl class="dl" style="margin-bottom:18px;">
            <dt>ผู้เขียน</dt><dd>{{ $announcement->author?->name ?? '—' }}</dd>
            <dt>วันที่เผยแพร่</dt><dd>{{ optional($announcement->published_at)->translatedFormat('j F Y, H:i') ?? 'ยังไม่เผยแพร่' }}</dd>
        </dl>

        <div style="line-height:1.8; color:var(--text-1);">
            {!! nl2br(e($announcement->body)) !!}
        </div>
    </x-card>
@endsection
