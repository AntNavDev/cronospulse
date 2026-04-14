{{--
    Pagination bar — page number controls + per-page selector.

    Props:
        paginator — LengthAwarePaginator instance
        perPage   — current per-page value (int; 0 = All)
        method    — Livewire method name called on per-page change (default: 'setPerPage')
        options   — array of per-page values (int; 0 = All) (default: [20, 50, 100, 0])

    Usage:
        <x-pagination-bar :paginator="$this->plants" :per-page="$perPage" />
        <x-pagination-bar :paginator="$this->exercises" :per-page="$perPage" :options="[20, 40, 80, 100, 0]" />
--}}
@props(['paginator', 'perPage', 'method' => 'setPerPage', 'options' => [20, 40, 80, 100, 0]])

<div class="mt-6">
    {{-- Mobile: page controls row, then showing/per-page row --}}
    <div class="sm:hidden flex flex-col gap-3">
        <div class="flex justify-center">
            <x-pagination-selector :paginator="$paginator" />
        </div>
        <div class="flex items-center justify-between">
            <div class="text-sm text-[var(--color-text-muted)]">
                @if ($paginator->total() > 0)
                    Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
                @endif
            </div>
            <x-per-page-selector :current="$perPage" :method="$method" :options="$options" />
        </div>
    </div>

    {{-- Desktop: 3-column layout --}}
    <div class="hidden sm:grid grid-cols-3 items-center gap-4">
        <div class="text-sm text-[var(--color-text-muted)]">
            @if ($paginator->total() > 0)
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @endif
        </div>
        <div class="flex justify-center">
            <x-pagination-selector :paginator="$paginator" />
        </div>
        <div class="flex justify-end">
            <x-per-page-selector :current="$perPage" :method="$method" :options="$options" />
        </div>
    </div>
</div>