<div class="pagination-wrap">
    @if ($show_summary ?? true)
        <div class="pagination-summary" role="status">
            Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} {{ $label ?? 'barang' }}
        </div>
    @endif
    @if ($paginator->hasPages())
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $startPage = max(1, $currentPage - 2);
            $endPage = min($lastPage, $currentPage + 2);
        @endphp
        <nav class="pagination-controls" aria-label="Navigasi halaman">
            @if ($paginator->onFirstPage())
                <span class="pagination-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">‹</span>
            @else
                <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            @if ($startPage > 1)
                <a class="pagination-link" href="{{ $paginator->url(1) }}" aria-label="Halaman 1">1</a>
                @if ($startPage > 2)
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @for ($page = $startPage; $page <= $endPage; $page++)
                @if ($page === $currentPage)
                    <span class="pagination-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                @else
                    <a class="pagination-link" href="{{ $paginator->url($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($endPage < $lastPage)
                @if ($endPage < $lastPage - 1)
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="pagination-link" href="{{ $paginator->url($lastPage) }}" aria-label="Halaman {{ $lastPage }}">{{ $lastPage }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="pagination-disabled" aria-disabled="true" aria-label="Halaman berikutnya">›</span>
            @endif
        </nav>
    @endif
</div>