@if ($paginator->hasPages())
    <div class="my-paginate my-4">
        <nav aria-label="page navigation">
            <ul class="pagination flex-wrap justify-content-center mb-0">
                {{-- Previous Page Link --}}
                @if (!$paginator->onFirstPage())
                    <li class="page-item">
                        <a href="{{ $paginator->previousPageUrl() }}" class="page-link rounded-3" aria-label="قبلی">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <a class="page-link rounded-3" href="javascript:void(0)" aria-label="قبلی">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled"><a class="page-link rounded-3" href="javascript:void(0)">{{ $element }}</a></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active"><a href="javascript:void(0)" class="page-link rounded-3">{{ $page }}</a></li>
                            @else
                                <li class="page-item"><a href="{{ $url }}" class="page-link rounded-3">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a href="{{ $paginator->nextPageUrl() }}" class="page-link rounded-3" aria-label="بعدی">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <a class="page-link rounded-3" href="javascript:void(0)" aria-label="بعدی">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
