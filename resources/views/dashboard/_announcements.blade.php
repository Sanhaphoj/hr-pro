<x-card title="ประกาศล่าสุด" :padding="false">
    <x-slot:actions>
        <a href="{{ route('announcements.index') }}" class="btn btn--ghost btn--sm">ดูทั้งหมด</a>
    </x-slot:actions>
    @if($announcements->isEmpty())
        <x-empty icon="megaphone" title="ยังไม่มีประกาศ" message="ประกาศจากฝ่ายบุคคลจะแสดงที่นี่" />
    @else
        <ul class="divide-list divide-list--inset">
            @foreach($announcements as $a)
                <li>
                    <div class="flex items-center gap-1" style="margin-bottom:5px;">
                        @if($a->pinned)<x-icon name="pin" width="14" height="14" />@endif
                        <x-status-badge type="announcement" :value="$a->category" />
                    </div>
                    <a href="{{ route('announcements.show', $a) }}" class="cell-strong">{{ $a->title }}</a>
                    <div class="person__meta">{{ thai_date($a->published_at) }}</div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
