@if ($paginator->hasPages())
<nav class="flex items-center justify-between gap-2 text-sm" aria-label="Pagination">
    <p class="text-ink-600">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>
    <div class="flex gap-2">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary btn-sm opacity-50" aria-disabled="true">← Previous</span>
        @else
            <a class="btn btn-secondary btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Previous</a>
        @endif
        @if ($paginator->hasMorePages())
            <a class="btn btn-secondary btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Next →</a>
        @else
            <span class="btn btn-secondary btn-sm opacity-50" aria-disabled="true">Next →</span>
        @endif
    </div>
</nav>
@endif
