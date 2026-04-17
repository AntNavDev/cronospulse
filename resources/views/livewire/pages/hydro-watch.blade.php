<x-slot:seo>
    <x-seo
        title="HydroWatch — Flood Alerts"
        description="Active NWS flood alerts across the United States. Browse current flood watches, warnings, and advisories by state with interactive maps."
        :canonical="url('/hydro-watch')"
    />
</x-slot:seo>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-text">HydroWatch</h1>
        <p class="mt-1 text-sm text-muted">
            Active NWS flood watches, warnings, and advisories across the United States. Select a state to view alerts on the map.
        </p>
    </div>

    {{-- Active flood alerts + national list --}}
    <div class="mb-10">
        @livewire('hydro.flood-alerts')
    </div>
</div>
