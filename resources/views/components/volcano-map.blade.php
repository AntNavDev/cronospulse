@props([
    'id'               => 'volcano-map',
    'height'           => '500px',
    'lat'              => 39.5,
    'lng'              => -98.35,
    'zoom'             => 4,
    'initialVolcanoes' => [],
])

{{--
    Reusable Leaflet map component for VolcanoWatch.

    Props:
      id                — unique DOM id for the map container
      height            — CSS height value for the map (default: 500px)
      lat               — initial centre latitude  (default: continental US)
      lng               — initial centre longitude (default: continental US)
      zoom              — initial zoom level       (default: 4)
      initialVolcanoes  — array of volcano objects to render as markers on load

    Browser events consumed:
      volcanoes-updated → { detail: { volcanoes } } — filtered volcano array from Livewire
--}}
<div
    x-data="volcanoMap({ elementId: '{{ $id }}', centerLat: {{ $lat }}, centerLng: {{ $lng }}, zoom: {{ $zoom }}, initialVolcanoes: {{ Js::from($initialVolcanoes) }} })"
    wire:ignore
    style="height: {{ $height }}; width: 100%;"
    class="overflow-hidden rounded-xl border border-border"
>
    <div id="{{ $id }}" style="height: 100%; width: 100%;"></div>
</div>