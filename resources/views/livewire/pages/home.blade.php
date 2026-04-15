<x-slot:seo>
    <x-seo
        title="Real-Time Geophysical Data"
        description="Live earthquake activity, streamflow levels, and water gauge readings across the United States — sourced directly from the U.S. Geological Survey."
        :canonical="url('/')"
    />
</x-slot:seo>

<div class="space-y-16">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-2xl">
        {{-- Animated gradient background --}}
        <div class="absolute inset-0 animate-gradient-shift bg-[linear-gradient(135deg,var(--color-bg),var(--color-surface-raised),var(--color-accent-subtle),var(--color-surface-raised),var(--color-bg))] bg-[length:300%_300%]"></div>

        <div class="relative px-8 py-16 sm:px-12 sm:py-24">
            <div class="max-w-2xl space-y-5">
                <h1 class="font-display text-4xl font-bold tracking-tight text-text sm:text-5xl">
                    CronosPulse
                </h1>
                <p class="text-lg leading-relaxed text-muted">
                    Real-time geophysical data from the U.S. Geological Survey — earthquakes,
                    streamflow levels, and water gauge readings across the United States,
                    visualised and updated the moment they're detected.
                </p>
                <div class="flex flex-wrap gap-3 pt-2">
                    <x-button variant="primary">Explore earthquakes</x-button>
                    <x-button variant="secondary">Browse stations</x-button>
                </div>
            </div>
        </div>
    </section>

    {{-- Data sources --}}
    <section class="space-y-8">
        <h2 class="text-2xl font-semibold text-text">Connected data sources</h2>

        <div class="grid gap-6 sm:grid-cols-2">

            <div class="rounded-xl border border-border bg-surface p-6 transition-colors hover:bg-surface-hover">
                <div class="mb-3 flex items-center gap-3">
                    <x-label variant="eq">Seismic</x-label>
                    <h3 class="font-semibold text-text">Earthquake Hazards</h3>
                </div>
                <p class="text-sm text-muted">
                    Real-time and historical seismic events from the USGS ComCat catalogue.
                    Every event includes magnitude, depth, location, PAGER alert level, and felt reports.
                    Data updates within minutes of detection.
                </p>
                <ul class="mt-4 space-y-1 text-sm text-muted">
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Magnitude, depth, and epicentre coordinates
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        ShakeMap instrumental intensity (MMI)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Community felt reports (Did You Feel It?)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        PAGER alert levels (green → red)
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-border bg-surface p-6 transition-colors hover:bg-surface-hover">
                <div class="mb-3 flex items-center gap-3">
                    <x-label variant="flood">Hydrology</x-label>
                    <h3 class="font-semibold text-text">Streamflow &amp; Water Levels</h3>
                </div>
                <p class="text-sm text-muted">
                    Time-series readings from thousands of USGS stream gauges via the National Water
                    Information System (NWIS). Data covers discharge rates, gage height, and water
                    temperature at active monitoring stations.
                </p>
                <ul class="mt-4 space-y-1 text-sm text-muted">
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Discharge / streamflow (ft³/s)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Gage height / water level (ft)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Water temperature (°C)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Provisional and approved readings
                    </li>
                </ul>
            </div>

        </div>
    </section>

</div>