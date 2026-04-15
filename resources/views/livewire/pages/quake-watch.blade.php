<x-slot:seo>
    <x-seo
        title="QuakeWatch — Earthquake Radius Search"
        description="Click any location on the map and set a mile radius to scope earthquake searches in that area."
        :canonical="url('/quake-watch')"
    />
</x-slot:seo>

{{--
    Alpine manages all map interaction state client-side — no Livewire round-trips needed
    until the user hits Search.

    Shared state (x-data on root):
      lat, lng       — coordinates of the last map click (null until first click)
      radius         — display value; kilometres when unit='km', miles when unit='mi'
      unit           — 'km' or 'mi' (display only — the API always receives kilometres)
      radiusKm       — getter that converts radius to km regardless of selected unit
      switchUnit     — converts the display value when toggling units
      dispatchRadius — sends metres to the map circle via map-radius-updated
--}}
<div
    x-data="{
        lat: null,
        lng: null,
        radius: 50,
        unit: 'km',

        get radiusKm() {
            return this.unit === 'mi'
                ? this.radius / 0.621371
                : this.radius;
        },

        switchUnit(newUnit) {
            if (this.unit === newUnit) return;
            this.radius = this.unit === 'km'
                ? Math.round(this.radius * 0.621371 * 10) / 10
                : Math.round(this.radius / 0.621371 * 10) / 10;
            this.unit = newUnit;
            this.dispatchRadius();
        },

        dispatchRadius() {
            window.dispatchEvent(
                new CustomEvent('map-radius-updated', { detail: { meters: this.radiusKm * 1000 } })
            );
        }
    }"
    @map-clicked.window="lat = $event.detail.lat; lng = $event.detail.lng"
>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-text">QuakeWatch</h1>
        <p class="mt-1 text-sm text-muted">
            Click anywhere on the map to set a search origin, then adjust the radius and hit Search.
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
                    Search radius (<span x-text="unit"></span>)
                </label>
                <input
                    id="quake-radius"
                    type="number"
                    min="1"
                    step="1"
                    placeholder="50"
                    x-model="radius"
                    @change="dispatchRadius()"
                    class="no-spin w-full rounded-lg border border-border bg-surface px-4 py-2.5 text-text placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
                />
            </div>

            {{-- Unit toggle --}}
            <div class="flex gap-5">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-text">
                    <input
                        type="radio"
                        name="radius-unit"
                        value="km"
                        x-model="unit"
                        @change="switchUnit('km')"
                        class="accent-[var(--color-accent)]"
                    />
                    Kilometers
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-text">
                    <input
                        type="radio"
                        name="radius-unit"
                        value="mi"
                        x-model="unit"
                        @change="switchUnit('mi')"
                        class="accent-[var(--color-accent)]"
                    />
                    Miles
                </label>
            </div>

            {{-- Selected location display --}}
            <div class="rounded-xl border border-border bg-surface p-5">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">
                    Selected location
                </p>

                <template x-if="lat === null">
                    <p class="text-sm text-muted">
                        Click anywhere on the map to select a location.
                    </p>
                </template>

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
                            <dd class="font-mono text-sm text-text">
                                <span x-text="radius"></span>&nbsp;<span x-text="unit"></span>
                            </dd>
                        </div>
                    </dl>
                </template>
            </div>

            {{-- Search button --}}
            <button
                type="button"
                :disabled="lat === null"
                @click="$wire.search(lat, lng, radiusKm)"
                class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-accent-hover focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/40 disabled:cursor-not-allowed disabled:opacity-40"
            >
                <span wire:loading.remove wire:target="search">Search</span>
                <span wire:loading wire:target="search">Searching…</span>
            </button>

        </div>
    </div>

    {{-- Results --}}
    <div class="mt-8">

        {{-- Error --}}
        @if ($error)
            <div class="rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
                {{ $error }}
            </div>
        @endif

        {{-- Loading skeleton --}}
        <div wire:loading wire:target="search" class="space-y-2">
            <div class="skeleton h-10 w-full rounded-lg"></div>
            @foreach (range(1, 8) as $_)
                <div class="skeleton h-8 w-full rounded"></div>
            @endforeach
        </div>

        {{-- No results --}}
        @if ($earthquakes !== null && count($earthquakes) === 0)
            <p class="text-sm text-muted">No earthquakes found for this location and radius.</p>
        @endif

        {{-- Results table --}}
        @if ($earthquakes !== null && count($earthquakes) > 0)
            <div wire:loading.remove wire:target="search">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted">
                    {{ count($earthquakes) }} result{{ count($earthquakes) === 1 ? '' : 's' }} — times in UTC
                </p>
                <div class="overflow-x-auto rounded-xl border border-border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-surface-sunken text-left">
                                <th class="px-4 py-3 font-semibold text-muted">Mag</th>
                                <th class="px-4 py-3 font-semibold text-muted">Location</th>
                                <th class="px-4 py-3 font-semibold text-muted">Time (UTC)</th>
                                <th class="px-4 py-3 font-semibold text-muted">Depth (km)</th>
                                <th class="px-4 py-3 font-semibold text-muted">Alert</th>
                                <th class="px-4 py-3 font-semibold text-muted">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($earthquakes as $quake)
                                <tr class="bg-surface transition hover:bg-surface-hover">
                                    <td class="px-4 py-3 font-mono font-semibold {{ $quake['mag_class'] }}">
                                        {{ number_format($quake['magnitude'], 1) }}
                                    </td>
                                    <td class="px-4 py-3 text-text">
                                        {{ $quake['place'] }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-muted">
                                        {{ $quake['time'] }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-muted">
                                        {{ $quake['depth_km'] }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($quake['alert'])
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-xs font-semibold capitalize',
                                                'bg-success/15 text-success'   => $quake['alert'] === 'green',
                                                'bg-warning/15 text-warning'   => in_array($quake['alert'], ['yellow', 'orange']),
                                                'bg-danger/15 text-danger'     => $quake['alert'] === 'red',
                                            ])>
                                                {{ $quake['alert'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 capitalize text-muted">
                                        {{ $quake['status'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($quake['url'])
                                            <a
                                                href="{{ $quake['url'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-xs text-accent hover:underline"
                                            >
                                                USGS&nbsp;↗
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>