@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="disabled" aria-disabled="true">‹ Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Précédent</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant ›</a>
        @else
            <span class="disabled" aria-disabled="true">Suivant ›</span>
        @endif
    </nav>
@endif
