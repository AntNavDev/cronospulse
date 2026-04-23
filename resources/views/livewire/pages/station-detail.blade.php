<x-slot:seo>
    <x-seo
        title="{{ $stationName }} — CronosPulse"
        description="30-day streamflow and gage height history for USGS station {{ $siteNo }} — {{ $stationName }}."
        :canonical="url('/hydro/station/' . $siteNo)"
    />
</x-slot:seo>

<div>

    {{-- Back link --}}
    <div class="mb-4">
        <a
            href="{{ route('hydro-watch') }}"
            class="inline-flex items-center gap-1.5 text-sm text-accent hover:underline"
        >
            ← Back to HydroWatch
        </a>
    </div>

    {{-- Error state --}}
    @if ($error)
        <div class="rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
            {{ $error }}
        </div>
    @endif

    @if ($stationMeta !== null)

        {{-- Station header --}}
        <div class="mb-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text">{{ $stationMeta['name'] }}</h1>
                    <p class="mt-1 text-sm text-muted">
                        USGS Site {{ $stationMeta['site_no'] }}
                        @if ($stationMeta['state'])
                            &middot; {{ $stationMeta['state'] }}
                        @endif
                    </p>
                </div>
                <a
                    href="https://waterdata.usgs.gov/monitoring-location/{{ $stationMeta['site_no'] }}/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-medium text-text transition hover:bg-surface-hover"
                >
                    View on USGS ↗
                </a>
            </div>
        </div>

        {{-- Metadata grid --}}
        <div class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">

            @php
                $siteTypeLabels = [
                    'ST'     => 'Stream',
                    'ST-CA'  => 'Canal',
                    'ST-DCH' => 'Ditch',
                    'ST-TS'  => 'Tidal stream',
                    'LK'     => 'Lake / reservoir',
                    'WE'     => 'Wetland',
                    'ES'     => 'Estuary',
                    'GW'     => 'Groundwater well',
                    'SB'     => 'Subsurface',
                    'SP'     => 'Spring',
                    'AT'     => 'Atmosphere',
                    'OC'     => 'Ocean',
                    'OC-CO'  => 'Coastal ocean',
                ];
            @endphp

            <div class="rounded-xl border border-border bg-surface p-4">
                <p class="mb-0.5 text-xs font-semibold uppercase tracking-wider text-muted">Site type</p>
                <p class="text-sm font-medium text-text">
                    {{ $siteTypeLabels[$stationMeta['site_type']] ?? $stationMeta['site_type'] }}
                </p>
            </div>

            <div class="rounded-xl border border-border bg-surface p-4">
                <p class="mb-0.5 text-xs font-semibold uppercase tracking-wider text-muted">State</p>
                <p class="text-sm font-medium text-text">{{ $stationMeta['state'] ?: '—' }}</p>
            </div>

            @if ($stationMeta['huc'])
                <div class="rounded-xl border border-border bg-surface p-4">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wider text-muted">HUC</p>
                    <p class="font-mono text-sm font-medium text-text">{{ $stationMeta['huc'] }}</p>
                </div>
            @endif

            <div class="rounded-xl border border-border bg-surface p-4">
                <p class="mb-0.5 text-xs font-semibold uppercase tracking-wider text-muted">Coordinates</p>
                <p class="font-mono text-sm font-medium text-text">
                    {{ number_format((float) $stationMeta['latitude'], 4) }},
                    {{ number_format((float) $stationMeta['longitude'], 4) }}
                </p>
            </div>

            @if ($stationMeta['elevation_ft'] !== null)
                <div class="rounded-xl border border-border bg-surface p-4">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wider text-muted">Elevation</p>
                    <p class="font-mono text-sm font-medium text-text">
                        {{ number_format((float) $stationMeta['elevation_ft'], 1) }} ft
                    </p>
                </div>
            @endif

        </div>

        {{-- 30-day charts --}}
        <div class="space-y-6">

            {{-- Streamflow --}}
            @if (! empty($streamflowChart['data']))
                <div class="rounded-xl border border-border bg-surface p-5">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-muted">30-day streamflow</p>
                    <p class="mb-4 text-sm text-muted">Discharge in ft³/s</p>
                    <div
                        wire:key="station-streamflow-{{ $siteNo }}"
                        wire:ignore
                        x-data="lineChart({
                            labels: {{ Js::from($streamflowChart['labels']) }},
                            datasets: [{
                                label: 'Streamflow (ft³/s)',
                                data: {{ Js::from($streamflowChart['data']) }},
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

            {{-- Gage height --}}
            @if (! empty($gageHeightChart['data']))
                <div class="rounded-xl border border-border bg-surface p-5">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-muted">30-day gage height</p>
                    <p class="mb-4 text-sm text-muted">Water level in feet</p>
                    <div
                        wire:key="station-gage-{{ $siteNo }}"
                        wire:ignore
                        x-data="lineChart({
                            labels: {{ Js::from($gageHeightChart['labels']) }},
                            datasets: [{
                                label: 'Gage height (ft)',
                                data: {{ Js::from($gageHeightChart['data']) }},
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

            @if (empty($streamflowChart['data']) && empty($gageHeightChart['data']))
                <div class="rounded-xl border border-border bg-surface p-8 text-center">
                    <p class="text-sm text-muted">No readings available for the last 30 days.</p>
                </div>
            @endif

        </div>

        {{-- Provisional data disclaimer --}}
        <p class="mt-6 text-xs text-muted">
            Data sourced from the USGS National Water Information System. Readings marked P are provisional and subject to revision.
        </p>

    @endif

</div>
