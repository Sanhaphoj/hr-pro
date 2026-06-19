@extends('layouts.app')
@section('title', 'ประกาศ')

@section('content')
    <x-page-header title="ประกาศ" subtitle="ข่าวสารและประกาศจากองค์กร">
        @can('announcements.manage')
            <x-slot:actions>
                <x-button href="{{ route('announcements.create') }}" icon="plus">เขียนประกาศ</x-button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <form method="GET" action="{{ route('announcements.index') }}" class="toolbar">
        <x-select
            name="category"
            :options="$categories"
            :selected="$category"
            placeholder="ทุกหมวดหมู่"
            onchange="this.form.submit()" />
        @if($category)
            <x-button href="{{ route('announcements.index') }}" variant="ghost" size="sm" icon="x">ล้างตัวกรอง</x-button>
        @endif
    </form>

    @if($announcements->isEmpty())
        <x-card>
            <x-empty icon="megaphone" title="ยังไม่มีประกาศ" message="เมื่อมีการเผยแพร่ประกาศ จะปรากฏที่นี่" />
        </x-card>
    @else
        <div class="grid">
            @foreach($announcements as $announcement)
                <x-card>
                    <div class="flex items-center gap-2" style="margin-bottom:8px; flex-wrap:wrap;">
                        @if($announcement->pinned)<x-icon name="pin" width="16" height="16" />@endif
                        <x-status-badge type="announcement" :value="$announcement->category" />
                        @if($canManage && ! $announcement->is_published)
                            <x-badge color="gray" :dot="true">ฉบับร่าง</x-badge>
                        @endif
                    </div>

                    <a href="{{ route('announcements.show', $announcement) }}" class="cell-strong" style="font-size:17px;">
                        {{ $announcement->title }}
                    </a>

                    <div class="cell-sub" style="margin:6px 0 12px;">
                        โดย {{ $announcement->author?->name ?? '—' }}
                        · {{ optional($announcement->published_at)->translatedFormat('j F Y') ?? 'ยังไม่เผยแพร่' }}
                    </div>

                    <p style="margin:0; color:var(--text-2);">{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 220) }}</p>

                    @if($canManage)
                        <div class="flex items-center gap-2" style="margin-top:14px;">
                            <x-button href="{{ route('announcements.show', $announcement) }}" variant="ghost" size="sm" icon="eye">ดู</x-button>
                            @can('announcements.manage')
                                <x-button href="{{ route('announcements.edit', $announcement) }}" variant="secondary" size="sm" icon="edit">แก้ไข</x-button>
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" data-confirm="ยืนยันการลบ?">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" size="sm" icon="trash">ลบ</x-button>
                                </form>
                            @endcan
                        </div>
                    @endif
                </x-card>
            @endforeach
        </div>

        <div style="margin-top:18px;">{{ $announcements->links() }}</div>
    @endif
@endsection
