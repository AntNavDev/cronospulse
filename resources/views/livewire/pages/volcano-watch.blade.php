<x-slot:seo>
    <x-seo
        title="VolcanoWatch — US Volcano Monitoring"
        description="Monitor real-time alert levels and aviation color codes for all USGS-tracked US volcanoes."
        :canonical="url('/volcano-watch')"
    />
</x-slot:seo>

{{--
    Alpine manages the map lifecycle client-side via the volcanoMap component.
    Initial markers are passed as JSON through x-data on first render.
    Subsequent filter/search changes dispatch the 'volcanoes-updated' browser event
    from Livewire, which the map picks up to refresh markers without a full page reload.

    Name search is handled server-side via wire:model.live.debounce.300ms so it
    applies before pagination (client-side x-show only filters the current page).
--}}
<div>

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-text">VolcanoWatch</h1>
        <p class="mt-1 text-sm text-muted">
            Real-time USGS monitoring data for volcanoes across the United States and territories.
        </p>
    </div>

    {{-- Elevated Volcanoes summary card — full width --}}
    @if ($volcanoes !== null && ! $error)
        <div class="mb-6">
            <x-volcano-elevated-card :counts="$elevatedCounts" />
        </div>
    @endif

    {{-- Map (left) + pie chart / filters / search (right) --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Left: map --}}
        <div>
            <x-volcano-map id="volcano-map" height="500px" :initial-volcanoes="$volcanoes ?? []" />
        </div>

        {{-- Right: pie chart → dropdowns → search --}}
        <div class="space-y-5">

            {{-- Alert level breakdown pie chart — wire:ignore because the data never changes after mount --}}
            @if ($volcanoes !== null && ! $error && count($chartData) > 0)
                <div wire:ignore class="rounded-xl border border-border bg-surface p-4">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-muted">
                        Alert Level Breakdown
                    </h3>
                    <div
                        x-data="pieChart({
                            labels: @js($chartLabels),
                            data: @js($chartData),
                            colorVars: @js($chartColors)
                        })"
                        class="mx-auto max-w-xs"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="flex flex-wrap gap-4">

                {{-- Region filter --}}
                <div class="flex-1">
                    <label for="volcano-region" class="mb-1.5 block text-sm font-medium text-text">
                        Region
                    </label>
                    <select
                        id="volcano-region"
                        wire:model.live="regionFilter"
                        class="w-full rounded-lg border border-border bg-surface px-4 py-2.5 text-text focus:border-accent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
                    >
                        <option value="">All regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Alert level filter --}}
                <div class="flex-1">
                    <label for="volcano-alert" class="mb-1.5 block text-sm font-medium text-text">
                        Alert level
                    </label>
                    <select
                        id="volcano-alert"
                        wire:model.live="alertFilter"
                        class="w-full rounded-lg border border-border bg-surface px-4 py-2.5 text-text focus:border-accent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
                    >
                        <option value="">All levels</option>
                        <option value="NORMAL">Normal</option>
                        <option value="ADVISORY">Advisory</option>
                        <option value="WATCH">Watch</option>
                        <option value="WARNING">Warning</option>
                    </select>
                </div>

            </div>

            {{-- Name search --}}
            <x-search-input
                wire:model.live.debounce.300ms="searchQuery"
                placeholder="Search volcanoes…"
            />

        </div>

    </div>

    {{-- Error --}}
    @if ($error)
        <div class="mb-4 rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
            {{ $error }}
        </div>
    @endif

    {{-- Loading skeleton --}}
    <div wire:loading wire:target="mount" class="mb-4 space-y-2">
        @foreach (range(1, 8) as $_)
            <div class="skeleton h-14 w-full rounded-lg"></div>
        @endforeach
    </div>

    {{-- Full-width table section --}}
    @if ($volcanoes !== null && ! $error)

        {{-- Result count (right) and Reset map (far right) --}}
        <div class="mb-2 flex items-center justify-end gap-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">
                {{ $filteredCount }} {{ $filteredCount === 1 ? 'volcano' : 'volcanoes' }}
                @if ($searchQuery !== '' || $regionFilter !== '' || $alertFilter !== '')
                    — filtered
                @endif
            </p>
            <button
                type="button"
                class="cursor-pointer text-xs text-accent hover:underline focus:outline-none"
                @click="
                    window.dispatchEvent(new CustomEvent('volcano-map-reset'));
                    $wire.resetFilters();
                "
            >
                Reset map
            </button>
        </div>

        {{-- No results --}}
        @if ($filteredCount === 0)
            <p class="text-sm text-muted">No volcanoes match the selected filters.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-surface-sunken text-left">
                            <th class="px-4 py-3 font-semibold text-muted">Volcano</th>
                            <th class="px-4 py-3 font-semibold text-muted">Region</th>
                            <th class="px-4 py-3 font-semibold text-muted">Alert</th>
                            <th class="px-4 py-3 font-semibold text-muted">Aviation</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($paginator->items() as $volcano)
                            <tr
                                class="cursor-pointer bg-surface transition hover:bg-surface-hover"
                                x-on:click="window.dispatchEvent(new CustomEvent('volcano-selected', { detail: { vnum: '{{ $volcano['vnum'] }}' } }))"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium text-text">{{ $volcano['name'] }}</div>
                                    @if ($volcano['synopsis'])
                                        <div class="mt-0.5 max-w-xs truncate text-xs text-muted" title="{{ $volcano['synopsis'] }}">
                                            {{ $volcano['synopsis'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-muted">
                                    {{ $volcano['region'] ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $volcano['alert_class'] }}">
                                        {{ $volcano['alert_level'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $volcano['color_class'] }}">
                                        {{ $volcano['color_code'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($volcano['url'])
                                        <a
                                            href="{{ $volcano['url'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-xs text-accent hover:underline"
                                        >
                                            USGS&nbsp;↗
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-pagination-bar
                :paginator="$paginator"
                :per-page="$perPage"
                :wire="true"
                :options="[10, 25, 50, 0]"
            />
        @endif

    @endif

</div>