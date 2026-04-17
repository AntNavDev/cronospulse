<x-slot:seo>
    <x-seo
        title="About"
        description="CronosPulse is an open data visualisation tool for real-time earthquake activity, volcano alert levels, and active flood warnings across the United States."
        :canonical="url('/about')"
    />
</x-slot:seo>

<div class="max-w-2xl space-y-8">

    <section class="space-y-4">
        <h1 class="text-4xl font-bold tracking-tight text-text">About CronosPulse</h1>
        <p class="text-lg text-muted">
            CronosPulse is an open data visualisation tool built on top of public APIs from the
            <a href="https://www.usgs.gov" target="_blank" rel="noopener noreferrer" class="text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                U.S. Geological Survey (USGS)
            </a>
            and the
            <a href="https://www.weather.gov" target="_blank" rel="noopener noreferrer" class="text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                National Weather Service (NWS)
            </a>.
            It surfaces real-time earthquake activity, volcano alert levels, and active flood
            warnings through interactive charts and maps.
        </p>
        <p class="text-muted">
            All data is sourced directly from US federal agencies and is subject to their data
            quality designations — some readings are provisional and may be revised before
            receiving final approval.
        </p>
    </section>

    <section class="space-y-4">
        <h2 class="text-xl font-semibold text-text">USGS API references</h2>
        <ul class="space-y-4 text-sm">
            <li>
                <a href="https://earthquake.usgs.gov/fdsnws/event/1/" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                    USGS Earthquake Hazards — FDSN Event Web Service
                </a>
                <p class="mt-0.5 text-muted">
                    Query seismic events by time, location, magnitude, and depth. Powers QuakeWatch.
                </p>
            </li>
            <li>
                <a href="https://volcanoes.usgs.gov/vhp/api.html" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                    USGS Volcano Hazards Program — VHP Status API
                </a>
                <p class="mt-0.5 text-muted">
                    Current alert levels and aviation colour codes for all monitored US volcanoes. Powers VolcanoWatch.
                </p>
            </li>
            <li>
                <a href="https://api.weather.gov/" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                    National Weather Service — Alerts API
                </a>
                <p class="mt-0.5 text-muted">
                    Active CAP alerts including flood watches, warnings, and advisories by zone and area. Powers HydroWatch.
                </p>
            </li>
            <li>
                <a href="https://www.usgs.gov/data" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                    USGS Data catalogue
                </a>
                <p class="mt-0.5 text-muted">
                    Full index of publicly available USGS datasets.
                </p>
            </li>
        </ul>
    </section>

</div>