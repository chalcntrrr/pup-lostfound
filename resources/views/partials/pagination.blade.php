@if ($paginator->hasPages())
    <nav class="pagination-bar" aria-label="Pages">
        @if ($paginator->onFirstPage())
            <span class="page-btn page-btn-disabled">&lsaquo;</span>
        @else
            <a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-btn page-btn-disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-btn-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
        @else
            <span class="page-btn page-btn-disabled">&rsaquo;</span>
        @endif
    </nav>
@endif
