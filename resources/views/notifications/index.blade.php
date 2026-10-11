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
            <ul class="divide-list divide-list--inset">
                @foreach($notifications as $n)
                    <li class="notif {{ is_null($n->read_at) ? 'is-unread' : '' }}">
                        <div class="stat__icon stat__icon--sm stat__icon--{{ ['success'=>'green','warning'=>'amber','error'=>'red'][$n->type] ?? 'blue' }}">
                            <x-icon :name="['success'=>'check-circle','warning'=>'alert','error'=>'x-circle'][$n->type] ?? 'info'" />
                        </div>
                        <div class="notif__text">
                            <div class="cell-strong">{{ $n->title }}</div>
                            <div class="cell-sub">{{ $n->message }}</div>
                            <div class="cell-sub">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="notif__action">
                            @if(is_null($n->read_at))
                                <form method="POST" action="{{ route('notifications.read', $n) }}">
                                    @csrf
                                    <x-button type="submit" variant="ghost" size="sm">{{ $n->link ? 'เปิดดู' : 'ทำเครื่องหมายอ่าน' }}</x-button>
                                </form>
                            @elseif($n->link)
                                <a href="{{ $n->link }}" class="btn btn--ghost btn--sm">เปิดดู</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="card__pager">{{ $notifications->links() }}</div>
        @endif
    </x-card>
@endsection
