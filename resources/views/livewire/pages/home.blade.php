<div class="space-y-16">

    {{-- Hero --}}
    <section class="space-y-4">
        <h1 class="text-4xl font-bold tracking-tight">
            Real-time geophysical data, visualised.
        </h1>
        <p class="max-w-2xl text-lg text-gray-600 dark:text-gray-400">
            CronosPulse connects directly to U.S. Geological Survey APIs to surface live and historical
            earthquake activity, streamflow levels, and water gauge readings across the United States.
        </p>
    </section>

    {{-- Data sources --}}
    <section class="space-y-8">
        <h2 class="text-2xl font-semibold">Connected data sources</h2>

        <div class="grid gap-6 sm:grid-cols-2">

            <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                <h3 class="mb-2 font-semibold">Earthquake Hazards</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Real-time and historical seismic events from the USGS ComCat catalogue.
                    Every event includes magnitude, depth, location, PAGER alert level, and felt reports.
                    Data updates within minutes of detection.
                </p>
                <ul class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-500">
                    <li>Magnitude, depth, and epicentre coordinates</li>
                    <li>ShakeMap instrumental intensity (MMI)</li>
                    <li>Community felt reports (Did You Feel It?)</li>
                    <li>PAGER alert levels (green → red)</li>
                </ul>
            </div>

            <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                <h3 class="mb-2 font-semibold">Streamflow &amp; Water Levels</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Time-series readings from thousands of USGS stream gauges via the National Water
                    Information System (NWIS). Data covers discharge rates, gage height, and water
                    temperature at active monitoring stations.
                </p>
                <ul class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-500">
                    <li>Discharge / streamflow (ft³/s)</li>
                    <li>Gage height / water level (ft)</li>
                    <li>Water temperature (°C)</li>
                    <li>Provisional and approved readings</li>
                </ul>
            </div>

        </div>
    </section>

</div>