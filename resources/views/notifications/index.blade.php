@extends('layouts.app')
@section('title', 'การแจ้งเตือน')

@section('content')
    <x-page-header title="การแจ้งเตือน" subtitle="การอัปเดตและกิจกรรมที่เกี่ยวข้องกับคุณ">
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <x-button type="submit" variant="secondary" icon="check">อ่านทั้งหมด</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        @if($notifications->isEmpty())
            <x-empty icon="bell" title="ยังไม่มีการแจ้งเตือน" message="เมื่อมีกิจกรรมใหม่ จะปรากฏที่นี่" />
        @else
            <ul class="divide-list" style="padding:0 20px;">
                @foreach($notifications as $n)
                    <li class="flex items-center gap-2" style="{{ is_null($n->read_at) ? 'background:#f5f9ff; margin:0 -20px; padding-left:20px; padding-right:20px;' : '' }}">
                        <div class="stat__icon stat__icon--{{ ['success'=>'green','warning'=>'amber','error'=>'red'][$n->type] ?? 'blue' }}" style="width:38px; height:38px;">
                            <x-icon :name="['success'=>'check-circle','warning'=>'alert','error'=>'x-circle'][$n->type] ?? 'info'" width="18" height="18" />
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div class="cell-strong">{{ $n->title }}</div>
                            <div class="cell-sub">{{ $n->message }}</div>
                            <div class="cell-sub">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                        @if(is_null($n->read_at))
                            <form method="POST" action="{{ route('notifications.read', $n) }}">
                                @csrf
                                <x-button type="submit" variant="ghost" size="sm">{{ $n->link ? 'เปิดดู' : 'ทำเครื่องหมายอ่าน' }}</x-button>
                            </form>
                        @elseif($n->link)
                            <a href="{{ $n->link }}" class="btn btn--ghost btn--sm">เปิดดู</a>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div style="padding:0 20px 12px;">{{ $notifications->links() }}</div>
        @endif
    </x-card>
@endsection
