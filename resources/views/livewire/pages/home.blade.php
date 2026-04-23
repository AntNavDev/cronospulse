<x-slot:seo>
    <x-seo
        title="Real-Time Geophysical Data"
        description="Live earthquake activity, volcano status, active flood alerts, and real-time stream gauge readings across the United States — sourced from USGS and the National Weather Service."
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
                    Real-time geophysical data from USGS and the National Weather Service —
                    earthquakes, volcano activity, active flood alerts, and live stream gauge readings
                    across the United States, visualised and updated as events unfold.
                </p>
                <div class="flex flex-wrap gap-2 pt-2">
                    <a href="{{ route('quake-watch') }}" style="background:var(--color-badge-eq-bg);color:var(--color-badge-eq-text);border:1px solid var(--color-badge-eq-border)"
                       class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium transition-opacity hover:opacity-75">
                        Earthquake Hazards →
                    </a>
                    <a href="{{ route('volcano-watch') }}" style="background:var(--color-badge-vol-bg);color:var(--color-badge-vol-text);border:1px solid var(--color-badge-vol-border)"
                       class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium transition-opacity hover:opacity-75">
                        Volcano Activity →
                    </a>
                    <a href="{{ route('hydro-watch') }}" style="background:var(--color-badge-flood-bg);color:var(--color-badge-flood-text);border:1px solid var(--color-badge-flood-border)"
                       class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium transition-opacity hover:opacity-75">
                        Flood & Streamflow →
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Recent seismic activity feed (read from DB, populated by app:ingest-earthquakes) --}}
    @livewire('recent-earthquakes')

    {{-- Data sources --}}
    <section class="space-y-8">
        <h2 class="text-2xl font-semibold text-text">Connected data sources</h2>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

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
                    <x-label variant="vol">Volcanic</x-label>
                    <h3 class="font-semibold text-text">Volcano Activity</h3>
                </div>
                <p class="text-sm text-muted">
                    Current alert levels and aviation colour codes for every monitored US volcano,
                    sourced from the USGS Volcano Hazards Program. Covers the full range from
                    background baseline to eruption-level alerts.
                </p>
                <ul class="mt-4 space-y-1 text-sm text-muted">
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Alert level (Normal → Warning)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Aviation colour code (Green → Red)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Location and monitoring network
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Filterable by alert level and state
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-border bg-surface p-6 transition-colors hover:bg-surface-hover">
                <div class="mb-3 flex items-center gap-3">
                    <x-label variant="flood">Flood</x-label>
                    <h3 class="font-semibold text-text">Flood & Streamflow Data</h3>
                </div>
                <p class="text-sm text-muted">
                    Active NWS flood watches, warnings, and advisories alongside live USGS stream
                    gauge readings. Flood zones are mapped as GeoJSON polygons; gauges show
                    real-time streamflow and gage height for hundreds of active monitoring sites
                    across the US.
                </p>
                <ul class="mt-4 space-y-1 text-sm text-muted">
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Flood watches, warnings, and advisories
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        CAP severity (Minor → Extreme)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        GeoJSON zone polygons on interactive map
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Live streamflow (ft³/s) and gage height (ft)
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        3-day sparkline charts per gauge site
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="size-1 rounded-full bg-accent-muted"></span>
                        Per-state map with hundreds of active sites
                    </li>
                </ul>
            </div>

        </div>
    </section>

</div>