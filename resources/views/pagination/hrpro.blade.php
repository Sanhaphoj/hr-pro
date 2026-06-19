@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="การแบ่งหน้า">
        <div class="pagination__info">
            แสดง {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
            จาก {{ number_format($paginator->total()) }} รายการ
        </div>

        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true"><x-icon name="chevron-left" width="16" height="16" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="ก่อนหน้า"><x-icon name="chevron-left" width="16" height="16" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="is-disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="ถัดไป"><x-icon name="chevron-right" width="16" height="16" /></a>
        @else
            <span class="is-disabled" aria-disabled="true"><x-icon name="chevron-right" width="16" height="16" /></span>
        @endif
    </nav>
@endif
