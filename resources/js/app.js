import Collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import tzLookup from 'tz-lookup';

/**
 * Register Alpine plugins and components inside alpine:init.
 *
 * Livewire 4 bundles and starts Alpine via @livewireScripts. We must NOT
 * import or start Alpine ourselves — doing so creates two instances, which
 * breaks Livewire's $wire magic and causes "Detected multiple instances"
 * warnings. Instead, hook into alpine:init, which fires after window.Alpine
 * is set but before Alpine.start() is called.
 */
document.addEventListener('alpine:init', () => {

    window.Alpine.plugin(Collapse);

    /**
     * Reusable Alpine.js component that renders a Chart.js line chart.
     *
     * Usage:
     *   <div x-data="lineChart({ labels: [...], datasets: [...] })">
     *     <canvas x-ref="canvas"></canvas>
     *   </div>
     *
     * chartData shape:
     *   { labels: string[], datasets: Chart.js dataset objects[] }
     */
    window.Alpine.data('lineChart', (chartData = { labels: [], datasets: [] }) => ({
        chart: null,
        chartData,

        init() {
            this.initChart();

            this.$watch('chartData', () => {
                this.destroyChart();
                this.initChart();
            });
        },

        destroy() {
            this.destroyChart();
        },

        initChart() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: this.chartData.labels,
                    datasets: this.chartData.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                },
            });
        },

        destroyChart() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },
    }));

    /**
     * Reusable Alpine.js component that renders a clickable Leaflet map.
     *
     * Usage:
     *   <div x-data="leafletMap({ elementId: 'my-map', centerLat: 39.5, centerLng: -98.35, zoom: 4 })">
     *     <div id="my-map" style="height: 100%; width: 100%;"></div>
     *   </div>
     *
     * Browser events dispatched:
     *   map-clicked         → { detail: { lat, lng } }
     *
     * Browser events listened for:
     *   map-radius-updated  → { detail: { meters } }    radius pre-converted to metres
     *   earthquakes-updated → { detail: { earthquakes } } array of earthquake objects from Livewire
     */
    window.Alpine.data('leafletMap', ({ elementId, centerLat = 39.5, centerLng = -98.35, zoom = 4 }) => ({
        map: null,
        circle: null,
        markerLayer: null,
        radiusMeters: 50 * 1000, // default 50 km in metres
        _radiusListener: null,
        _earthquakeListener: null,

        init() {
            this.map = L.map(elementId).setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
            }).addTo(this.map);

            // Marker cluster group — holds all earthquake circle markers.
            this.markerLayer = L.markerClusterGroup({ chunkedLoading: true });
            this.map.addLayer(this.markerLayer);

            this.map.on('click', (e) => {
                const { lat, lng } = e.latlng;

                if (this.circle) {
                    this.circle.setLatLng([lat, lng]).setRadius(this.radiusMeters);
                } else {
                    this.circle = L.circle([lat, lng], {
                        radius: this.radiusMeters,
                        color: 'var(--color-accent)',
                        fillColor: 'var(--color-accent)',
                        fillOpacity: 0.15,
                        weight: 2,
                    }).addTo(this.map);
                }

                // Zoom to fit the circle with a small padding so it fills the viewport.
                this.map.flyToBounds(this.circle.getBounds(), { padding: [24, 24] });

                // Resolve the IANA timezone for the clicked coordinates so the
                // results table can display local event times without a paid API.
                const timezone = tzLookup(lat, lng);

                window.dispatchEvent(new CustomEvent('map-clicked', {
                    detail: { lat, lng, timezone },
                }));
            });

            // Listen for radius changes dispatched from the page.
            // The event carries pre-calculated metres so the map doesn't need
            // to know which unit the user has selected.
            this._radiusListener = (e) => {
                const meters = Number(e.detail.meters);
                if (!meters || meters < 1) return;
                this.radiusMeters = meters;
                if (this.circle) {
                    this.circle.setRadius(this.radiusMeters);
                    this.map.flyToBounds(this.circle.getBounds(), { padding: [24, 24] });
                }
            };

            // Listen for earthquake data from Livewire. Clears stale markers
            // on every search, including when results are empty or an error occurred.
            this._earthquakeListener = (e) => {
                this.renderMarkers(e.detail.earthquakes ?? []);
            };

            window.addEventListener('map-radius-updated', this._radiusListener);
            window.addEventListener('earthquakes-updated', this._earthquakeListener);
        },

        /**
         * Clear existing markers and render a fresh set for the given earthquakes.
         *
         * Uses L.marker with a divIcon (a styled div rendered as a coloured circle)
         * rather than L.circleMarker. divIcon-based markers are proper Leaflet Marker
         * instances, so leaflet.markercluster handles click events, popup anchoring,
         * and spiderfy animations correctly. L.circleMarker is an SVG layer and loses
         * reliable click events when pulled out of a cluster group.
         */
        renderMarkers(earthquakes) {
            this.markerLayer.clearLayers();

            earthquakes.forEach((quake) => {
                const { color, size } = this.markerStyle(quake.magnitude);
                const half = size / 2;

                const icon = L.divIcon({
                    className: '',
                    html: `<div style="
                        width:${size}px;
                        height:${size}px;
                        border-radius:50%;
                        background-color:${color};
                        border:2.5px solid rgba(255,255,255,0.95);
                        box-shadow:0 2px 6px rgba(0,0,0,0.4);
                        opacity:0.95;
                    "></div>`,
                    iconSize: [size, size],
                    iconAnchor: [half, half],
                    popupAnchor: [0, -(half + 4)],
                });

                const marker = L.marker([quake.lat, quake.lng], { icon });
                marker.bindPopup(this.popupContent(quake), { minWidth: 200 });
                this.markerLayer.addLayer(marker);
            });
        },

        /**
         * Return a colour and pixel diameter for a marker icon based on magnitude.
         *
         * Colours are read from CSS custom properties so they respect the active
         * theme. Four tiers map to the four semantic colour variables.
         */
        markerStyle(magnitude) {
            if (magnitude >= 5.0) return { color: this.cssVar('--color-danger'),  size: 34 };
            if (magnitude >= 4.0) return { color: this.cssVar('--color-warning'), size: 26 };
            if (magnitude >= 2.0) return { color: this.cssVar('--color-info'),    size: 20 };
            return                       { color: this.cssVar('--color-success'), size: 14 };
        },

        /**
         * Read a CSS custom property value from the root element.
         */
        cssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        /**
         * Build the HTML string for a marker popup.
         *
         * Inline styles are required here — the popup lives in a Leaflet-managed
         * DOM node outside Tailwind's scope.
         */
        popupContent(quake) {
            const esc = (str) => {
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            };

            const link = quake.url
                ? `<div style="margin-top:8px;"><a href="${esc(quake.url)}" target="_blank" rel="noopener noreferrer" style="color:#6366f1;font-size:12px;">View on USGS ↗</a></div>`
                : '';

            return `
                <div style="font-family:sans-serif;font-size:13px;line-height:1.6;">
                    <div style="font-size:17px;font-weight:700;margin-bottom:2px;">M${quake.magnitude.toFixed(1)}</div>
                    <div style="color:#374151;margin-bottom:8px;">${esc(quake.place)}</div>
                    <table style="width:100%;border-collapse:collapse;font-size:12px;color:#6b7280;">
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Depth</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${quake.depth_km} km</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Lat / Lng</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${quake.lat.toFixed(4)}, ${quake.lng.toFixed(4)}</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Time (UTC)</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${esc(quake.time)}</td>
                        </tr>
                    </table>
                    ${link}
                </div>
            `;
        },

        destroy() {
            window.removeEventListener('map-radius-updated', this._radiusListener);
            window.removeEventListener('earthquakes-updated', this._earthquakeListener);
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        },
    }));

    /**
     * Alpine.js component that renders a Leaflet map for VolcanoWatch.
     *
     * Unlike leafletMap (which is click-driven), volcanoMap is data-driven:
     * it renders markers for all volcanoes passed in on init, then updates
     * whenever Livewire dispatches a 'volcanoes-updated' browser event (e.g.
     * after a state or alert level filter changes).
     *
     * Usage:
     *   <div x-data="volcanoMap({ elementId: 'volcano-map', initialVolcanoes: [...] })">
     *     <div id="volcano-map" style="height: 100%; width: 100%;"></div>
     *   </div>
     *
     * Browser events listened for:
     *   volcanoes-updated  → { detail: { volcanoes } }  filtered volcano array from Livewire
     *   volcano-selected   → { detail: { vnum } }        zoom to marker and open its popup
     */
    window.Alpine.data('volcanoMap', ({ elementId, centerLat = 39.5, centerLng = -98.35, zoom = 4, initialVolcanoes = [] }) => ({
        map: null,
        markerLayer: null,
        /** @type {Record<string, L.Marker>} Markers indexed by vnum for direct lookup. */
        markers: {},
        _volcanoListener: null,
        _selectListener: null,
        _resetListener: null,

        init() {
            this.map = L.map(elementId).setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
            }).addTo(this.map);

            this.markerLayer = L.markerClusterGroup({ chunkedLoading: true });
            this.map.addLayer(this.markerLayer);

            // Render markers for the initial volcano set passed from Blade.
            this.renderMarkers(initialVolcanoes);

            // Listen for filter-driven updates dispatched by Livewire lifecycle hooks.
            this._volcanoListener = (e) => {
                this.renderMarkers(e.detail.volcanoes ?? []);
            };

            // Fly to a specific volcano and open its popup when a table row is clicked.
            this._selectListener = (e) => {
                const marker = this.markers[e.detail.vnum];
                if (! marker) return;
                // Fly to the marker's position at a close zoom so it's the clear focus.
                // Open the popup once the animation finishes — doing it mid-flight causes
                // it to anchor to the wrong screen position.
                this.map.flyTo(marker.getLatLng(), 9, { duration: 0.8 });
                this.map.once('moveend', () => marker.openPopup());
            };

            // Fly back to the initial center and zoom level.
            this._resetListener = () => {
                this.map.flyTo([centerLat, centerLng], zoom, { duration: 0.8 });
            };

            window.addEventListener('volcanoes-updated', this._volcanoListener);
            window.addEventListener('volcano-selected', this._selectListener);
            window.addEventListener('volcano-map-reset', this._resetListener);
        },

        /**
         * Clear existing markers and render a fresh set for the given volcanoes.
         *
         * Each marker is a divIcon circle coloured by USGS alert level.
         * Markers are stored in this.markers keyed by vnum for O(1) lookup
         * when a table row is clicked.
         */
        renderMarkers(volcanoes) {
            this.markerLayer.clearLayers();
            this.markers = {};

            volcanoes.forEach((volcano) => {
                if (! volcano.latitude || ! volcano.longitude) return;

                const color = this.alertLevelColor(volcano.alert_level);
                const size  = 20;
                const half  = size / 2;

                const icon = L.divIcon({
                    className: '',
                    html: `<div style="
                        width:${size}px;
                        height:${size}px;
                        border-radius:50%;
                        background-color:${color};
                        border:2.5px solid rgba(255,255,255,0.95);
                        box-shadow:0 2px 6px rgba(0,0,0,0.4);
                        opacity:0.95;
                    "></div>`,
                    iconSize: [size, size],
                    iconAnchor: [half, half],
                    popupAnchor: [0, -(half + 4)],
                });

                const marker = L.marker([volcano.latitude, volcano.longitude], { icon });
                marker.bindPopup(this.popupContent(volcano), { minWidth: 220 });
                this.markerLayer.addLayer(marker);

                if (volcano.vnum) {
                    this.markers[volcano.vnum] = marker;
                }
            });
        },

        /**
         * Return a CSS color value for the given USGS ground alert level.
         *
         * Reads from CSS custom properties so it respects the active theme.
         * Levels in ascending severity: NORMAL → ADVISORY → WATCH → WARNING.
         */
        alertLevelColor(alertLevel) {
            switch (alertLevel) {
                case 'WARNING':  return this.cssVar('--color-danger');
                case 'WATCH':    return this.cssVar('--color-warning');
                case 'ADVISORY': return this.cssVar('--color-info');
                case 'NORMAL':   return this.cssVar('--color-success');
                default:         return this.cssVar('--color-muted');
            }
        },

        /**
         * Read a CSS custom property value from the root element.
         */
        cssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        /**
         * Build the HTML string for a volcano marker popup.
         *
         * Inline styles are required — the popup lives in a Leaflet-managed
         * DOM node outside Tailwind's scope.
         */
        popupContent(volcano) {
            const esc = (str) => {
                const d = document.createElement('div');
                d.textContent = str ?? '';
                return d.innerHTML;
            };

            const synopsis = volcano.synopsis
                ? `<div style="margin-top:8px;font-size:12px;color:#6b7280;line-height:1.5;">${esc(volcano.synopsis)}</div>`
                : '';

            const link = volcano.url
                ? `<div style="margin-top:8px;"><a href="${esc(volcano.url)}" target="_blank" rel="noopener noreferrer" style="color:#6366f1;font-size:12px;">View on USGS ↗</a></div>`
                : '';

            return `
                <div style="font-family:sans-serif;font-size:13px;line-height:1.6;">
                    <div style="font-size:16px;font-weight:700;margin-bottom:2px;">${esc(volcano.name)}</div>
                    <div style="color:#6b7280;font-size:12px;margin-bottom:8px;">${esc(volcano.region)}</div>
                    <table style="width:100%;border-collapse:collapse;font-size:12px;color:#6b7280;">
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Alert level</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${esc(volcano.alert_level)}</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Aviation code</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${esc(volcano.color_code)}</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Lat / Lng</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${volcano.latitude.toFixed(4)}, ${volcano.longitude.toFixed(4)}</td>
                        </tr>
                    </table>
                    ${synopsis}
                    ${link}
                </div>
            `;
        },

        destroy() {
            window.removeEventListener('volcanoes-updated', this._volcanoListener);
            window.removeEventListener('volcano-selected', this._selectListener);
            window.removeEventListener('volcano-map-reset', this._resetListener);
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        },
    }));

});