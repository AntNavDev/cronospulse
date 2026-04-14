{{--
    Per-page count selector — a themed dropdown.

    Props:
        current — the currently active per-page value (int; 0 = All)
        method  — Livewire method to call with the selected value (default: 'setPerPage')

    Usage:
        <x-per-page-selector :current="$perPage" />
        <x-per-page-selector :current="$perPage" method="changePageSize" />
--}}
@props(['current', 'method' => 'setPerPage', 'options' => [20, 40, 80, 100, 0]])

<div class="flex items-center gap-2">
    <span class="text-sm text-[var(--color-text-muted)] shrink-0">Per page</span>
    <x-select
        wire:change="{{ $method }}($event.target.value)"
        class="h-9 bg-[var(--color-surface-raised)]"
    >
        @foreach ($options as $option)
            <option value="{{ $option }}" @selected($option === $current)>
                {{ $option === 0 ? 'All' : $option }}
            </option>
        @endforeach
    </x-select>
</div>
