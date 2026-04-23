<x-slot:seo>
    <x-seo
        title="HydroWatch — Flood Alerts &amp; Stream Gauges"
        description="Active NWS flood alerts and USGS stream gauge readings across the United States. Browse flood watches and warnings by state, and track real-time streamflow and gage height."
        :canonical="url('/hydro-watch')"
    />
</x-slot:seo>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-text">HydroWatch</h1>
        <p class="mt-1 text-sm text-muted">
            Active NWS flood alerts and USGS stream gauges across the United States.
        </p>
    </div>

    {{-- Active flood alerts + national list --}}
    <div class="mb-10">
        @livewire('hydro.flood-alerts')
    </div>

    {{-- Stream gauges map + sparkline panel --}}
    <div>
        @livewire('hydro.stream-gauge')
    </div>
</div>
