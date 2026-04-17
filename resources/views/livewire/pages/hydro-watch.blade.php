<x-slot:seo>
    <x-seo
        title="HydroWatch — Stream & Flood Monitoring"
        description="Real-time USGS stream gauge data for the United States. Monitor current streamflow, gage height, and flood conditions by state."
        :canonical="url('/hydro-watch')"
    />
</x-slot:seo>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-text">HydroWatch</h1>
        <p class="mt-1 text-sm text-muted">
            Real-time USGS stream gauge monitoring and active NWS flood alerts by state.
        </p>
    </div>

    {{-- Active flood alerts map --}}
    <div class="mb-10">
        @livewire('hydro.flood-alerts')
    </div>

    {{-- Stream gauge map dashboard --}}
    <div>
        @livewire('hydro.stream-gauge')
    </div>
</div>