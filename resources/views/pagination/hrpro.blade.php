{{--
    The count line renders whether or not there is more than one page: a filter
    that matches seven rows should still say so, and an empty result should say
    that too. Only the page buttons are conditional.
--}}
<nav class="pagination" role="navigation" aria-label="การแบ่งหน้า">
    <div class="pagination__info">
        @if ($paginator->total() === 0)
            ไม่พบรายการ
        @elseif ($paginator->hasPages())
            แสดง {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }}
            จาก {{ number_format($paginator->total()) }} รายการ
        @else
            {{ number_format($paginator->total()) }} รายการ
        @endif
    </div>

    @if ($paginator->hasPages())
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
    @endif
</nav>
