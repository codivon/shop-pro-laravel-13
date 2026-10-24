{{-- صفحه‌بندی سانیار — $paginator (LengthAwarePaginator) --}}
@if ($paginator->hasPages())
    <nav aria-label="صفحه‌بندی محصولات" class="mt-4">
        <ul class="pagination flex-wrap justify-content-center mb-0">
            {{-- قبلی --}}
            @if (!$paginator->onFirstPage())
                <li class="page-item">
                    <a class="page-link rounded-3" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="صفحه قبلی">قبلی</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true"><span class="page-link rounded-3">قبلی</span></li>
            @endif

            @foreach ($elements as $element)
                {{-- جداکننده «...» --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link rounded-3">{{ $element }}</span></li>
                @endif

                {{-- شماره صفحات --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link rounded-3">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link rounded-3" href="{{ $url }}" aria-label="صفحه {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- بعدی --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link rounded-3" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="صفحه بعدی">بعدی</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true"><span class="page-link rounded-3">بعدی</span></li>
            @endif
        </ul>
    </nav>
@endif
