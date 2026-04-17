{{--
    StreamGauge component.

    Polling: wire:poll.300s="refreshSites" keeps current readings fresh every 5 minutes.
    The map is wire:ignore — Alpine's Leaflet instance survives Livewire re-renders.
    After each load, Livewire dispatches 'stream-gauges-updated' so the map refreshes markers.

    Site selection: clicking a popup button dispatches the 'stream-gauge-selected' browser event.
    The root div listens for it and calls $wire.selectSite() to load the 3-day sparkline.
--}}
<div
    wire:poll.300s="refreshSites"
    @stream-gauge-selected.window="$wire.selectSite($event.detail.siteCode)"
>

    {{-- Section header + state selector --}}
    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-text">Stream Gauges</h2>
            <p class="mt-0.5 text-sm text-muted">
                Active USGS stream monitoring sites. Click a row or map pin to load a 3-day chart.
            </p>
        </div>

        <div class="w-44">
            <label for="stream-state" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-muted">
                State
            </label>
            <select
                id="stream-state"
                wire:model.live="stateCd"
                class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-accent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
            >
                @foreach (\App\Livewire\Hydro\StreamGauge::US_STATES as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Error --}}
    @if ($error)
        <div class="mb-4 rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
            {{ $error }}
        </div>
    @endif

    {{-- Loading skeleton --}}
    @if ($sites === null && ! $error)
        <div class="skeleton h-[500px] w-full rounded-xl"></div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Map --}}
            <div>
                <x-stream-gauge-map
                    id="stream-gauge-map"
                    height="500px"
                    :initial-sites="$sites ?? []"
                />

                @if ($sites !== null)
                    <div class="mt-2 flex items-center justify-between text-xs text-muted">
                        <span>{{ count($sites) }} active sites</span>
                        <button
                            type="button"
                            class="cursor-pointer text-accent hover:underline focus:outline-none"
                            @click="window.dispatchEvent(new CustomEvent('stream-gauge-map-reset'))"
                        >
                            Reset map
                        </button>
                    </div>
                @endif
            </div>

            {{-- Sparkline panel --}}
            <div>

                @if ($selectedSiteCode === null)
                    <div class="h-[500px] overflow-hidden rounded-xl border border-border bg-surface">
                        @if (empty($sites))
                            <div class="flex h-full items-center justify-center">
                                <p class="text-sm text-muted">No active gauge sites found for this state.</p>
                            </div>
                        @else
                            <div class="flex h-full flex-col">
                                <div class="border-b border-border px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Current Readings</p>
                                    <p class="mt-0.5 text-xs text-muted">Sorted by streamflow — click a row for a 3-day chart</p>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="sticky top-0 bg-surface">
                                            <tr class="border-b border-border text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                <th class="px-4 py-2">Site</th>
                                                <th class="px-4 py-2 text-right">Flow (ft³/s)</th>
                                                <th class="px-4 py-2 text-right">Height (ft)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sites as $site)
                                                <tr
                                                    wire:click="selectSite('{{ $site['site_code'] }}')"
                                                    class="cursor-pointer border-b border-border/50 transition-colors hover:bg-surface-hover"
                                                >
                                                    <td class="px-4 py-2.5">
                                                        <p class="font-medium text-text leading-snug">{{ $site['site_name'] }}</p>
                                                        @if ($site['is_provisional'])
                                                            <span class="text-xs text-warning">Provisional</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums text-text">
                                                        {{ $site['streamflow'] !== null ? number_format((float) $site['streamflow'], 1) : '—' }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums text-text">
                                                        {{ $site['gage_height'] !== null ? number_format((float) $site['gage_height'], 2) : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif ($sparklineData === null && ! $error)
                    <div class="flex h-[500px] items-center justify-center rounded-xl border border-border bg-surface">
                        <div wire:loading wire:target="selectSite">
                            <p class="text-sm text-muted">Loading chart…</p>
                        </div>
                    </div>
                @elseif ($sparklineData !== null)
                    <div class="h-[500px] overflow-y-auto rounded-xl border border-border bg-surface p-5">

                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted">
                                    3-day chart
                                </p>
                                <h3 class="mt-0.5 text-sm font-semibold text-text">
                                    {{ $sparklineData['site_name'] }}
                                </h3>
                            </div>
                            <button
                                type="button"
                                wire:click="$set('selectedSiteCode', null)"
                                class="shrink-0 text-xs text-muted hover:text-text focus:outline-none"
                            >
                                ✕ Close
                            </button>
                        </div>

                        {{-- Streamflow chart --}}
                        @if (! empty($sparklineData['streamflow']['data']))
                            <div class="mb-5">
                                <p class="mb-2 text-xs font-medium text-muted">Streamflow (ft³/s)</p>
                                <div
                                    wire:key="sparkline-streamflow-{{ $selectedSiteCode }}"
                                    wire:ignore
                                    x-data="lineChart({
                                        labels: {{ Js::from($sparklineData['streamflow']['labels']) }},
                                        datasets: [{
                                            label: 'Streamflow (ft³/s)',
                                            data: {{ Js::from($sparklineData['streamflow']['data']) }},
                                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-accent').trim(),
                                            backgroundColor: 'transparent',
                                            pointRadius: 0,
                                            tension: 0.3,
                                            borderWidth: 2,
                                        }]
                                    })"
                                >
                                    <canvas x-ref="canvas"></canvas>
                                </div>
                            </div>
                        @endif

                        {{-- Gage height chart --}}
                        @if (! empty($sparklineData['gage_height']['data']))
                            <div>
                                <p class="mb-2 text-xs font-medium text-muted">Gage Height (ft)</p>
                                <div
                                    wire:key="sparkline-gage-{{ $selectedSiteCode }}"
                                    wire:ignore
                                    x-data="lineChart({
                                        labels: {{ Js::from($sparklineData['gage_height']['labels']) }},
                                        datasets: [{
                                            label: 'Gage Height (ft)',
                                            data: {{ Js::from($sparklineData['gage_height']['data']) }},
                                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-info').trim(),
                                            backgroundColor: 'transparent',
                                            pointRadius: 0,
                                            tension: 0.3,
                                            borderWidth: 2,
                                        }]
                                    })"
                                >
                                    <canvas x-ref="canvas"></canvas>
                                </div>
                            </div>
                        @endif

                        @if (empty($sparklineData['streamflow']['data']) && empty($sparklineData['gage_height']['data']))
                            <p class="text-sm text-muted">No readings available for the selected period.</p>
                        @endif

                    </div>
                @endif

            </div>
        </div>
    @endif

</div>