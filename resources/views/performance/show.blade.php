@extends('layouts.app')
@section('title', 'ผลการประเมิน')

@section('content')
    <x-page-header :title="'ผลการประเมิน · '.$review->period" :subtitle="$review->employee?->full_name">
        <x-slot:breadcrumb><a href="{{ route('performance.index') }}">← กลับรายการประเมินผล</a></x-slot:breadcrumb>
        @can('performance.manage')
            <x-slot:actions>
                <form method="POST" action="{{ route('performance.destroy', $review) }}" data-confirm="ลบผลการประเมินนี้?">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm" icon="trash">ลบ</x-button>
                </form>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <div style="max-width:680px;">
        <x-card>
            <div class="flex items-center justify-between" style="margin-bottom:16px;">
                <div class="flex items-center gap-2">
                    <span class="avatar avatar--lg" style="background: {{ avatar_color($review->employee?->email) }}">{{ $review->employee?->initials }}</span>
                    <div>
                        <div class="cell-strong" style="font-size:16px;">{{ $review->employee?->full_name ?? '—' }}</div>
                        <div class="cell-sub">{{ $review->employee?->position?->title ?? '—' }} · {{ $review->employee?->department?->name ?? '—' }}</div>
                    </div>
                </div>
                <x-badge :color="$review->score >= 4 ? 'green' : ($review->score >= 3 ? 'amber' : 'red')">คะแนน {{ $review->score }}/5</x-badge>
            </div>

            <dl class="dl">
                <dt>งวดการประเมิน</dt><dd>{{ $review->period }}</dd>
                <dt>ผู้ประเมิน</dt><dd>{{ $review->reviewer?->name ?? '—' }}</dd>
                <dt>วันที่ประเมิน</dt><dd>{{ optional($review->reviewed_at)->translatedFormat('j F Y') ?? '—' }}</dd>
            </dl>

            <h4 style="margin:18px 0 6px;">จุดแข็ง</h4>
            <p style="white-space:pre-line; margin:0;">{{ $review->strengths ?: '—' }}</p>

            <h4 style="margin:18px 0 6px;">สิ่งที่ควรพัฒนา</h4>
            <p style="white-space:pre-line; margin:0;">{{ $review->improvements ?: '—' }}</p>
        </x-card>
    </div>
@endsection
