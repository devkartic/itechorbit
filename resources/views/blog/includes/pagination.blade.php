{{-- Run in terminal for publish pagination: php artisan vendor:publish --tag=laravel-pagination--}}
<div class="container my-4 px-0">
    <nav aria-label="Page navigation example">
        <ul class="pagination pagination-md justify-content-start">
            @if ($paginator->previousPageUrl())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" tabindex="-1"
                       aria-disabled="true">Previous</a>
                </li>
            @else
                <li class="page-item disabled">
                    <a class="page-link" tabindex="-1"
                       aria-disabled="true">Previous</a>
                </li>
            @endif

            @foreach ($elements as $element)

                @if (is_string($element))
                    <li class="page-item disabled"><span>{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active"><a class="page-link">{{ $page }}</a></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->nextPageUrl())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}">Next</a>
                </li>
            @else
                <li class="page-item disabled">
                    <a class="page-link">Next</a>
                </li>
            @endif

        </ul>
    </nav>
</div>
