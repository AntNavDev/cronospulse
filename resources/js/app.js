import Collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

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

                window.dispatchEvent(new CustomEvent('map-clicked', {
                    detail: { lat, lng },
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

});