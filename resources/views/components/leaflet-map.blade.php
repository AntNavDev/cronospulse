@props([
    'id'     => 'leaflet-map',
    'height' => '500px',
    'lat'    => 39.5,
    'lng'    => -98.35,
    'zoom'   => 4,
])

{{--
    Reusable Leaflet map component.

    Props:
      id      — unique DOM id for the map container (required when using multiple maps per page)
      height  — CSS height value for the map (default: 500px)
      lat     — initial centre latitude  (default: continental US)
      lng     — initial centre longitude (default: continental US)
      zoom    — initial zoom level       (default: 4)

    Browser events dispatched:
      map-clicked         → CustomEvent with { detail: { lat, lng } }

    Browser events consumed:
      map-radius-updated  → CustomEvent with { detail: { radius } } — radius in miles
--}}
<div
    x-data="leafletMap({ elementId: '{{ $id }}', centerLat: {{ $lat }}, centerLng: {{ $lng }}, zoom: {{ $zoom }} })"
    wire:ignore
    style="height: {{ $height }}; width: 100%;"
    class="overflow-hidden rounded-xl border border-border"
>
    <div id="{{ $id }}" style="height: 100%; width: 100%;"></div>
</div>