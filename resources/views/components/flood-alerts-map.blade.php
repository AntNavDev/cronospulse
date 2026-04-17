@props([
    'id'            => 'flood-alerts-map',
    'height'        => '500px',
    'lat'           => 39.5,
    'lng'           => -98.35,
    'zoom'          => 6,
    'initialAlerts' => [],
])

{{--
    Reusable Leaflet map component for the FloodAlerts dashboard.

    Props:
      id             — unique DOM id for the map container
      height         — CSS height value for the map (default: 500px)
      lat            — initial centre latitude  (default: continental US)
      lng            — initial centre longitude (default: continental US)
      zoom           — initial zoom level       (default: 6)
      initialAlerts  — array of alert objects to render as GeoJSON polygons on load

    Browser events dispatched:
      flood-alert-selected  → { detail: { alertId } }  user clicked a polygon or "View details"

    Browser events consumed:
      flood-alerts-updated  → { detail: { alerts } }   fresh alert array from Livewire
      flood-alert-focus     → { detail: { alertId } }  fly to + open popup for a specific alert
      flood-alerts-map-reset → (no detail)              fly back to initial view
--}}
<div
    x-data="floodAlertsMap({ elementId: '{{ $id }}', centerLat: {{ $lat }}, centerLng: {{ $lng }}, zoom: {{ $zoom }}, initialAlerts: {{ Js::from($initialAlerts) }} })"
    wire:ignore
    style="height: {{ $height }}; width: 100%;"
    class="overflow-hidden rounded-xl border border-border"
>
    <div id="{{ $id }}" style="height: 100%; width: 100%;"></div>
</div>