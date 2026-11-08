@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-8 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-muted">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <div class="flex flex-wrap gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-line px-3 py-2 text-muted" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-line px-3 py-2 hover:bg-canvas">Previous</a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))<span class="px-2 py-2 text-muted">{{ $element }}</span>@endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="rounded-lg bg-brand-600 px-3 py-2 font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-lg border border-line px-3 py-2 hover:bg-canvas" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-line px-3 py-2 hover:bg-canvas">Next</a>
            @else
                <span class="rounded-lg border border-line px-3 py-2 text-muted" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
