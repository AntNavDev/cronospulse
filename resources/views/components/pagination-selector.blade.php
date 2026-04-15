{{--
    Pagination number selector.

    Props:
        paginator — a LengthAwarePaginator instance (from ->paginate())

    Usage:
        <x-pagination-selector :paginator="$this->plants" />
--}}
@props(['paginator'])

@php
    $currentPage = $paginator->currentPage();
    $lastPage    = $paginator->lastPage();

    // Build page list with ellipsis for large ranges
    $window = 1;
    $pages  = [];
    for ($i = 1; $i <= $lastPage; $i++) {
        if ($i === 1 || $i === $lastPage || abs($i - $currentPage) <= $window) {
            $pages[] = $i;
        } elseif (end($pages) !== '...') {
            $pages[] = '...';
        }
    }
@endphp

@if ($lastPage > 1)
    <nav aria-label="Pagination">
        <div class="flex items-center justify-center gap-1">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--color-border)] text-sm text-[var(--color-text-muted)] opacity-40 cursor-not-allowed select-none"
                    aria-disabled="true"
                    aria-label="Previous page"
                >
                    &larr;
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    wire:navigate
                    aria-label="Previous page"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-raised)] text-sm text-[var(--color-text)] hover:bg-[var(--color-border)] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]"
                >
                    &larr;
                </a>
            @endif

            {{-- Page numbers / ellipsis — always visible; pagination-bar handles mobile/desktop row separation --}}
            @foreach ($pages as $page)
                @if ($page === '...')
                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-[var(--color-text-muted)] select-none" aria-hidden="true">
                        &hellip;
                    </span>
                @elseif ($page === $currentPage)
                    <span
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--color-accent)] bg-[var(--color-accent)] text-sm font-semibold text-[var(--color-text-on-accent)] select-none"
                        aria-current="page"
                        aria-label="Page {{ $page }}, current"
                    >
                        {{ $page }}
                    </span>
                @else
                    <a
                        href="{{ $paginator->url($page) }}"
                        wire:navigate
                        aria-label="Page {{ $page }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-raised)] text-sm text-[var(--color-text)] hover:bg-[var(--color-border)] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]"
                    >
                        {{ $page }}
                    </a>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    wire:navigate
                    aria-label="Next page"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-raised)] text-sm text-[var(--color-text)] hover:bg-[var(--color-border)] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]"
                >
                    &rarr;
                </a>
            @else
                <span
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--color-border)] text-sm text-[var(--color-text-muted)] opacity-40 cursor-not-allowed select-none"
                    aria-disabled="true"
                    aria-label="Next page"
                >
                    &rarr;
                </span>
            @endif

        </div>
    </nav>
@endif