@if($paginator->hasPages())
    <nav class="pager" aria-label="Phân trang">
        @if($paginator->onFirstPage())
            <span class="button" aria-disabled="true">← Trước</span>
        @else
            <a class="button" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Trước</a>
        @endif

        @foreach($elements as $element)
            @if(is_string($element))
                <span class="pager-ellipsis" aria-hidden="true">…</span>
            @else
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())
                        <span class="button primary" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="button" href="{{ $url }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <a class="button" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau →</a>
        @else
            <span class="button" aria-disabled="true">Sau →</span>
        @endif
    </nav>
@endif
