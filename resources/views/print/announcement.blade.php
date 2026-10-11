@extends('layouts.print')

@php
    $categories = ['general' => 'ทั่วไป', 'policy' => 'นโยบาย', 'event' => 'กิจกรรม', 'urgent' => 'ด่วน'];
    // Blank lines in the body mark paragraphs; single line breaks stay inside one.
    $paragraphs = array_values(array_filter(preg_split('/\R\s*\R/u', trim((string) $announcement->body))));
    $issuedAt = $announcement->published_at ?? $announcement->created_at;
@endphp

@section('title', 'ประกาศ · '.$announcement->title)
@section('filename', 'ประกาศ_'.\Illuminate\Support\Str::limit($announcement->title, 60, ''))
@section('back', route('announcements.show', $announcement))

@section('content')
    <article class="sheet">
        @unless($announcement->is_published)
            <div class="doc-watermark" aria-hidden="true"><span>ฉบับร่าง</span></div>
        @endunless

        <x-doc.letterhead center />

        <div class="letter-meta" style="margin-top: 0;">
            <div>
                @if($announcement->category === 'urgent')
                    <span class="doc-flag">ด่วน</span>
                @else
                    <span class="doc-flag doc-flag--ink">{{ $categories[$announcement->category] ?? 'ประกาศ' }}</span>
                @endif
            </div>
            <div class="letter-meta__right">ที่ {{ doc_no('ANN', $announcement->id, $issuedAt) }}</div>
        </div>

        <div class="notice__head">
            @php
                $company = (string) config('hrpro.company.name');
                // Thai runs straight on ("ประกาศบริษัท …"); a Latin name needs the space.
                $joiner = preg_match('/^\p{Thai}/u', $company) ? '' : ' ';
            @endphp
            <div class="notice__org">ประกาศ{{ $joiner }}{{ $company }}</div>
            <div class="notice__subject">เรื่อง {{ $announcement->title }}</div>
            <div class="notice__rule"></div>
        </div>

        <div class="letter">
            @forelse($paragraphs as $paragraph)
                <p>{!! nl2br(e(trim($paragraph))) !!}</p>
            @empty
                <p>—</p>
            @endforelse
            <p style="margin-top: 5mm;">จึงประกาศมาเพื่อทราบโดยทั่วกัน</p>
            <p>ประกาศ ณ วันที่ {{ thai_date($issuedAt) }}</p>
        </div>

        <div class="signs signs--end">
            <x-doc.signature role="ผู้ออกประกาศ" :name="$announcement->author?->name" :position="config('hrpro.company.name')" :dated="false" />
        </div>

        <x-doc.footer />
    </article>
@endsection
