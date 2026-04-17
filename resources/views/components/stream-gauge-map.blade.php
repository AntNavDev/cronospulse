@props([
    'id'           => 'stream-gauge-map',
    'height'       => '500px',
    'lat'          => 39.5,
    'lng'          => -98.35,
    'zoom'         => 6,
    'initialSites' => [],
])

{{--
    Reusable Leaflet map component for the StreamGauge dashboard.

    Props:
      id            — unique DOM id for the map container
      height        — CSS height value for the map (default: 500px)
      lat           — initial centre latitude  (default: continental US)
      lng           — initial centre longitude (default: continental US)
      zoom          — initial zoom level       (default: 6)
      initialSites  — array of site objects to render as markers on load

    Browser events dispatched:
      stream-gauge-selected → { detail: { siteCode } }   user clicked "Load chart" in popup

    Browser events consumed:
      stream-gauges-updated → { detail: { sites } }      fresh site array from Livewire
      stream-gauge-map-reset → (no detail)                fly back to initial view
--}}
<div
    x-data="streamGaugeMap({ elementId: '{{ $id }}', centerLat: {{ $lat }}, centerLng: {{ $lng }}, zoom: {{ $zoom }}, initialSites: {{ Js::from($initialSites) }} })"
    wire:ignore
    style="height: {{ $height }}; width: 100%;"
    class="overflow-hidden rounded-xl border border-border"
>
    <div id="{{ $id }}" style="height: 100%; width: 100%;"></div>
</div>