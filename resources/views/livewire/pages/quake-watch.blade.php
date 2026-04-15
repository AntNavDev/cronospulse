<x-slot:seo>
    <x-seo
        title="QuakeWatch — Earthquake Radius Search"
        description="Click any location on the map and set a mile radius to scope earthquake searches in that area."
        :canonical="url('/quake-watch')"
    />
</x-slot:seo>

{{--
    Alpine manages all map interaction state client-side — no Livewire round-trips needed.

    Shared state (x-data on root):
      lat, lng  — coordinates of the last map click (null until first click)
      radius    — search radius in miles, bound to the input and forwarded to the map
--}}
<div
    x-data="{ lat: null, lng: null, radius: 50 }"
    @map-clicked.window="lat = $event.detail.lat; lng = $event.detail.lng"
>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-text">QuakeWatch</h1>
        <p class="mt-1 text-sm text-muted">
            Click anywhere on the map to set a search origin, then adjust the radius below.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Left column: map --}}
        <div>
            <x-leaflet-map id="quake-map" height="600px" />
        </div>

        {{-- Right column: controls + coordinate display --}}
        <div class="space-y-5">

            {{-- Radius input --}}
            <div>
                <label for="quake-radius" class="mb-1.5 block text-sm font-medium text-text">
                    Search radius (miles)
                </label>
                <input
                    id="quake-radius"
                    type="number"
                    min="1"
                    placeholder="50"
                    x-model="radius"
                    @change="window.dispatchEvent(new CustomEvent('map-radius-updated', { detail: { radius: Number($el.value) } }))"
                    class="no-spin w-full rounded-lg border border-border bg-surface px-4 py-2.5 text-text placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
                />
            </div>

            {{-- Selected location display --}}
            <div class="rounded-xl border border-border bg-surface p-5">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">
                    Selected location
                </p>

                {{-- Placeholder before any click --}}
                <template x-if="lat === null">
                    <p class="text-sm text-muted">
                        Click anywhere on the map to select a location.
                    </p>
                </template>

                {{-- Coordinate readout --}}
                <template x-if="lat !== null">
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-muted">Latitude</dt>
                            <dd class="font-mono text-sm text-text" x-text="lat.toFixed(6)"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-sm text-muted">Longitude</dt>
                            <dd class="font-mono text-sm text-text" x-text="lng.toFixed(6)"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-border pt-3">
                            <dt class="text-sm text-muted">Radius</dt>
                            <dd class="font-mono text-sm text-text" x-text="radius + ' mi'"></dd>
                        </div>
                    </dl>
                </template>
            </div>

        </div>
    </div>
</div>