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
     * Reusable Alpine.js component that renders a Chart.js doughnut (pie) chart.
     *
     * Usage:
     *   <div x-data="pieChart({ labels: [...], data: [...], colorVars: [...] })">
     *     <canvas x-ref="canvas"></canvas>
     *   </div>
     *
     * chartData shape:
     *   labels    — string[] display labels (one per slice)
     *   data      — number[] slice values
     *   colorVars — string[] CSS custom property names (e.g. '--color-danger') per slice.
     *               Resolved at render time so they respect the active theme.
     */
    window.Alpine.data('pieChart', (chartData = { labels: [], data: [], colorVars: [] }) => ({
        chart: null,

        init() {
            this.initChart();
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },

        cssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        initChart() {
            const colors = chartData.colorVars.map((v) => this.cssVar(v));

            this.chart = new Chart(this.$refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: colors,
                        borderColor: this.cssVar('--color-surface'),
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: this.cssVar('--color-text-muted'),
                                padding: 14,
                                font: { size: 12 },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / ctx.dataset.data.reduce((a, b) => a + b, 0) * 100)}%)`,
                            },
                        },
                    },
                },
            });
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
        _locationListener: null,
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

            // Place (or move) the circle at the given coordinates and zoom to it.
            // Used when pre-populating the map from a saved search re-run, where
            // there is no map-click event to create the circle automatically.
            this._locationListener = (e) => {
                const { lat, lng, meters } = e.detail;
                if (!lat || !lng) return;
                if (meters) this.radiusMeters = Number(meters);
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
                this.map.flyToBounds(this.circle.getBounds(), { padding: [24, 24] });
            };

            // Listen for earthquake data from Livewire. Clears stale markers
            // on every search, including when results are empty or an error occurred.
            this._earthquakeListener = (e) => {
                this.renderMarkers(e.detail.earthquakes ?? []);
            };

            window.addEventListener('map-radius-updated', this._radiusListener);
            window.addEventListener('map-location-set', this._locationListener);
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
            window.removeEventListener('map-location-set', this._locationListener);
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

            this.markerLayer = L.markerClusterGroup({
                chunkedLoading: true,
                // At zoom 8+ markers render individually with no cluster-dissolution
                // animation, preventing a markercluster race condition where the
                // animation callback fires after _map has been set to null.
                disableClusteringAtZoom: 8,
                spiderfyOnMaxZoom: false,
            });
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

    /**
     * Alpine.js component that renders a Leaflet map for the StreamGauge dashboard.
     *
     * Data-driven: renders markers for all stream gauge sites passed in on init,
     * then updates whenever Livewire dispatches a 'stream-gauges-updated' event
     * (after a state change or poll refresh). Markers are colored by gage height
     * using the same placeholder bands used in FloodWatch.
     *
     * Clicking a marker shows a popup with current readings and a "Load chart" button.
     * The button dispatches 'stream-gauge-selected' so the Livewire component can
     * call selectSite() and load the sparkline without needing $wire in a popup string.
     *
     * Usage:
     *   <div x-data="streamGaugeMap({ elementId: 'stream-gauge-map', initialSites: [...] })">
     *     <div id="stream-gauge-map" style="height: 100%; width: 100%;"></div>
     *   </div>
     *
     * Browser events dispatched:
     *   stream-gauge-selected  → { detail: { siteCode } }   user clicked "Load chart"
     *
     * Browser events listened for:
     *   stream-gauges-updated  → { detail: { sites } }      fresh site array from Livewire
     *   stream-gauge-map-reset → (no detail)                 fly back to initial view
     */
    /**
     * Approximate bounding boxes for US states and territories.
     * Format: [[south, west], [north, east]] — suitable for L.latLngBounds().
     */
    const STATE_BOUNDS = {
        AL: [[30.14, -88.47], [35.01, -84.89]], AK: [[51.21, -179.14], [71.38, -129.98]],
        AZ: [[31.33, -114.82], [37.00, -109.04]], AR: [[33.00, -94.62], [36.50, -89.64]],
        CA: [[32.53, -124.48], [42.01, -114.13]], CO: [[36.99, -109.06], [41.00, -102.04]],
        CT: [[40.95, -73.73], [42.05, -71.79]], DE: [[38.45, -75.79], [39.84, -75.05]],
        FL: [[24.52, -87.63], [31.00, -80.03]], GA: [[30.36, -85.61], [35.00, -80.84]],
        HI: [[18.91, -160.25], [22.24, -154.81]], ID: [[41.99, -117.24], [49.00, -111.04]],
        IL: [[36.97, -91.51], [42.51, -87.50]], IN: [[37.77, -88.10], [41.76, -84.78]],
        IA: [[40.38, -96.64], [43.50, -90.14]], KS: [[36.99, -102.05], [40.00, -94.59]],
        KY: [[36.50, -89.57], [39.15, -81.96]], LA: [[28.93, -94.04], [33.02, -88.82]],
        ME: [[43.06, -71.08], [47.46, -66.95]], MD: [[37.91, -79.49], [39.72, -74.98]],
        MA: [[41.24, -73.51], [42.89, -69.93]], MI: [[41.70, -90.42], [48.31, -82.41]],
        MN: [[43.50, -97.24], [49.38, -89.49]], MS: [[30.17, -91.65], [35.01, -88.10]],
        MO: [[35.99, -95.77], [40.61, -89.10]], MT: [[44.36, -116.05], [49.00, -104.04]],
        NE: [[40.00, -104.05], [43.00, -95.31]], NV: [[35.00, -120.00], [42.00, -114.04]],
        NH: [[42.70, -72.56], [45.31, -70.61]], NJ: [[38.93, -75.56], [41.36, -73.89]],
        NM: [[31.33, -109.05], [37.00, -103.00]], NY: [[40.50, -79.76], [45.01, -71.86]],
        NC: [[33.75, -84.32], [36.59, -75.46]], ND: [[45.93, -104.05], [49.00, -96.55]],
        OH: [[38.40, -84.82], [41.98, -80.52]], OK: [[33.62, -103.00], [37.00, -94.43]],
        OR: [[41.99, -124.57], [46.29, -116.46]], PA: [[39.72, -80.52], [42.27, -74.69]],
        RI: [[41.15, -71.91], [42.02, -71.12]], SC: [[32.05, -83.35], [35.22, -78.54]],
        SD: [[42.48, -104.06], [45.94, -96.44]], TN: [[34.98, -90.31], [36.68, -81.65]],
        TX: [[25.84, -106.65], [36.50, -93.51]], UT: [[36.99, -114.05], [42.00, -109.04]],
        VT: [[42.73, -73.44], [45.02, -71.50]], VA: [[36.54, -83.68], [39.47, -75.24]],
        WA: [[45.54, -124.79], [49.00, -116.92]], WV: [[37.20, -82.64], [40.64, -77.72]],
        WI: [[42.49, -92.89], [47.08, -86.25]], WY: [[41.00, -111.06], [45.01, -104.05]],
        PR: [[17.92, -67.27], [18.52, -65.58]], VI: [[17.68, -65.08], [18.40, -64.56]],
        GU: [[13.23, 144.62], [13.65, 144.96]], AS: [[-14.38, -171.09], [-11.05, -168.15]],
    };

    window.Alpine.data('streamGaugeMap', ({ elementId, centerLat = 39.5, centerLng = -98.35, zoom = 4, initialSites = [] }) => ({
        map: null,
        markerLayer: null,
        _sitesListener: null,
        _resetListener: null,

        init() {
            this.map = L.map(elementId).setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
            }).addTo(this.map);

            this.markerLayer = L.markerClusterGroup({ chunkedLoading: true });
            this.map.addLayer(this.markerLayer);

            this.renderMarkers(initialSites);

            this._sitesListener = (e) => {
                this.renderMarkers(e.detail.sites ?? [], e.detail.fitBounds ?? false, e.detail.stateCd ?? null);
            };

            this._resetListener = () => {
                this.map.flyTo([centerLat, centerLng], zoom, { duration: 0.8 });
            };

            window.addEventListener('stream-gauges-updated', this._sitesListener);
            window.addEventListener('stream-gauge-map-reset', this._resetListener);
        },

        /**
         * Clear and re-render all site markers from the given sites array.
         *
         * When fitBounds is true (state change), the map zooms to the known
         * bounding box for the given state — giving the full state outline
         * rather than just fitting to wherever USGS gauges happen to cluster.
         * Falls back to marker bounds if the state is not in STATE_BOUNDS.
         * When false (poll refresh), the user's pan/zoom position is preserved.
         */
        renderMarkers(sites, fitBounds = false, stateCd = null) {
            this.markerLayer.clearLayers();

            sites.forEach((site) => {
                if (! site.lat || ! site.lng) return;

                const color = this.stageColor(site.gage_height);
                const size  = 18;
                const half  = size / 2;

                const icon = L.divIcon({
                    className: '',
                    html: `<div style="
                        width:${size}px;
                        height:${size}px;
                        border-radius:50%;
                        background-color:${color};
                        border:2px solid rgba(255,255,255,0.95);
                        box-shadow:0 1px 4px rgba(0,0,0,0.35);
                    "></div>`,
                    iconSize: [size, size],
                    iconAnchor: [half, half],
                    popupAnchor: [0, -(half + 4)],
                });

                const marker = L.marker([site.lat, site.lng], { icon });
                marker.bindPopup(this.popupContent(site), { minWidth: 230 });
                this.markerLayer.addLayer(marker);
            });

            if (fitBounds) {
                const knownBounds = stateCd ? STATE_BOUNDS[stateCd.toUpperCase()] : null;
                if (knownBounds) {
                    this.map.flyToBounds(knownBounds, { padding: [20, 20], duration: 0.8 });
                } else if (this.markerLayer.getLayers().length > 0) {
                    // Fallback: fit to marker cluster bounds for territories without a lookup entry.
                    setTimeout(() => {
                        const bounds = this.markerLayer.getBounds();
                        if (bounds.isValid()) {
                            this.map.flyToBounds(bounds, { padding: [40, 40], duration: 0.8 });
                        }
                    }, 50);
                }
            }
        },

        /**
         * Return a CSS color for the gage height reading using the same bands as FloodWatch.
         * Falls back to muted for null values.
         */
        stageColor(gageHeight) {
            if (gageHeight === null || gageHeight === undefined) return this.cssVar('--color-text-muted');
            if (gageHeight > 20.0) return this.cssVar('--color-danger');
            if (gageHeight > 10.0) return this.cssVar('--color-warning');
            if (gageHeight >  5.0) return this.cssVar('--color-info');
            return this.cssVar('--color-success');
        },

        /**
         * Build the HTML string for a site marker popup.
         *
         * The "Load 7-day chart" button dispatches 'stream-gauge-selected' via
         * window.dispatchEvent so the StreamGauge Livewire component can call
         * selectSite() without needing $wire inside a popup string.
         */
        popupContent(site) {
            const esc = (str) => {
                const d = document.createElement('div');
                d.textContent = str ?? '—';
                return d.innerHTML;
            };

            const streamflow = site.streamflow !== null && site.streamflow !== undefined
                ? `${Number(site.streamflow).toLocaleString()} ft³/s`
                : '—';
            const gageHeight = site.gage_height !== null && site.gage_height !== undefined
                ? `${Number(site.gage_height).toFixed(2)} ft`
                : '—';

            return `
                <div style="font-family:sans-serif;font-size:13px;line-height:1.6;">
                    <div style="font-size:14px;font-weight:700;margin-bottom:6px;">${esc(site.site_name)}</div>
                    <table style="width:100%;border-collapse:collapse;font-size:12px;color:#6b7280;">
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Streamflow</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${streamflow}</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Gage height</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${gageHeight}</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 8px 2px 0;">Site</td>
                            <td style="text-align:right;font-weight:600;color:#111827;">${esc(site.site_code)}</td>
                        </tr>
                    </table>
                    <div style="margin-top:10px;">
                        <button
                            onclick="window.dispatchEvent(new CustomEvent('stream-gauge-selected', { detail: { siteCode: '${esc(site.site_code)}' } }))"
                            style="width:100%;padding:5px 0;background:${this.cssVar('--color-accent')};color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;"
                        >
                            Load 3-day chart
                        </button>
                    </div>
                    ${site.is_provisional ? '<div style="margin-top:6px;font-size:11px;color:#9ca3af;">P — Provisional data</div>' : ''}
                </div>
            `;
        },

        /**
         * Read a CSS custom property value from the root element.
         */
        cssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        destroy() {
            window.removeEventListener('stream-gauges-updated', this._sitesListener);
            window.removeEventListener('stream-gauge-map-reset', this._resetListener);
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        },
    }));

    /**
     * Alpine.js component that renders a Leaflet map for the FloodAlerts dashboard.
     *
     * Data-driven: renders NWS alert polygons as GeoJSON layers on init, then
     * updates whenever Livewire dispatches a 'flood-alerts-updated' event.
     * Polygons are coloured by CAP severity. Clicking a polygon dispatches
     * 'flood-alert-selected' so the Livewire component can show the detail panel.
     *
     * Alerts without geometry are excluded from the map (shown in list only).
     *
     * Usage:
     *   <div x-data="floodAlertsMap({ elementId: 'flood-alerts-map', initialAlerts: [...] })">
     *     <div id="flood-alerts-map" style="height: 100%; width: 100%;"></div>
     *   </div>
     *
     * Browser events dispatched:
     *   flood-alert-selected → { detail: { alertId } }   user clicked a polygon
     *
     * Browser events listened for:
     *   flood-alerts-updated    → { detail: { alerts } }    fresh alert array from Livewire
     *   flood-alert-focus       → { detail: { alertId } }   fly to + highlight a specific alert
     *   flood-alerts-map-reset  → (no detail)               fly back to initial view
     *   flood-alerts-state-zoom → { detail: { stateCd } }   zoom to a US state bounding box
     */
    window.Alpine.data('floodAlertsMap', ({ elementId, centerLat = 39.5, centerLng = -98.35, zoom = 4, initialAlerts = [] }) => ({
        map: null,
        geoJsonLayer: null,
        _alertsListener: null,
        _focusListener: null,
        _resetListener: null,
        _stateZoomListener: null,

        init() {
            this.map = L.map(elementId).setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
            }).addTo(this.map);

            this.renderAlerts(initialAlerts);

            this._alertsListener = (e) => {
                this.renderAlerts(e.detail.alerts ?? []);
            };

            // When Livewire selects an alert from the list, fly to its polygon and open its popup.
            this._focusListener = (e) => {
                if (! this.geoJsonLayer) return;
                this.geoJsonLayer.eachLayer((layer) => {
                    if (layer.feature && layer.feature.properties && layer.feature.properties.id === e.detail.alertId) {
                        const bounds = layer.getBounds ? layer.getBounds() : null;
                        if (bounds && bounds.isValid()) {
                            this.map.flyToBounds(bounds, { padding: [30, 30], duration: 0.8 });
                        }
                        if (layer.openPopup) layer.openPopup();
                    }
                });
            };

            this._resetListener = () => {
                this.map.flyTo([centerLat, centerLng], zoom, { duration: 0.8 });
            };

            // Zoom to a state bounding box when the state selector changes.
            this._stateZoomListener = (e) => {
                const bounds = STATE_BOUNDS[(e.detail.stateCd ?? '').toUpperCase()];
                if (bounds) {
                    this.map.flyToBounds(bounds, { padding: [20, 20], duration: 0.8 });
                }
            };

            window.addEventListener('flood-alerts-updated', this._alertsListener);
            window.addEventListener('flood-alert-focus', this._focusListener);
            window.addEventListener('flood-alerts-map-reset', this._resetListener);
            window.addEventListener('flood-alerts-state-zoom', this._stateZoomListener);
        },

        /**
         * Clear existing polygons and render fresh GeoJSON layers for the given alerts.
         *
         * Only alerts with geometry are rendered. Each polygon is styled by CAP severity
         * and emits 'flood-alert-selected' on click so Livewire can show the detail panel.
         */
        renderAlerts(alerts) {
            if (this.geoJsonLayer) {
                this.map.removeLayer(this.geoJsonLayer);
                this.geoJsonLayer = null;
            }

            const features = alerts
                .filter((a) => a.geometry !== null && a.geometry !== undefined)
                .map((a) => ({
                    type: 'Feature',
                    id: a.id,
                    properties: { id: a.id, severity: a.severity, event: a.event, headline: a.headline },
                    geometry: a.geometry,
                }));

            if (features.length === 0) return;

            this.geoJsonLayer = L.geoJSON({ type: 'FeatureCollection', features }, {
                style: (feature) => this.polygonStyle(feature.properties.severity),

                onEachFeature: (feature, layer) => {
                    layer.bindPopup(this.popupContent(feature.properties), { minWidth: 220 });

                    layer.on('click', () => {
                        window.dispatchEvent(new CustomEvent('flood-alert-selected', {
                            detail: { alertId: feature.properties.id },
                        }));
                    });
                },
            }).addTo(this.map);
        },

        /**
         * Return a Leaflet path style object based on CAP severity.
         *
         * Reads from CSS custom properties so colours respect the active theme.
         * Fill opacity is kept low so underlying map tiles remain readable.
         */
        polygonStyle(severity) {
            const color = this.severityColor(severity);
            return {
                color,
                weight: 2,
                opacity: 0.9,
                fillColor: color,
                fillOpacity: 0.18,
            };
        },

        /**
         * Return a CSS color string for the given CAP severity level.
         */
        severityColor(severity) {
            switch (severity) {
                case 'Extreme':  return this.cssVar('--color-danger');
                case 'Severe':   return this.cssVar('--color-danger');
                case 'Moderate': return this.cssVar('--color-warning');
                case 'Minor':    return this.cssVar('--color-info');
                default:         return this.cssVar('--color-text-muted');
            }
        },

        /**
         * Build the HTML string for a polygon popup.
         *
         * Inline styles are required — the popup lives in a Leaflet-managed
         * DOM node outside Tailwind's scope.
         */
        popupContent(props) {
            const esc = (str) => {
                const d = document.createElement('div');
                d.textContent = str ?? '';
                return d.innerHTML;
            };

            const color = this.severityColor(props.severity);

            return `
                <div style="font-family:sans-serif;font-size:13px;line-height:1.6;">
                    <div style="font-size:14px;font-weight:700;margin-bottom:4px;color:${color};">${esc(props.event)}</div>
                    <div style="color:#374151;font-size:12px;line-height:1.5;">${esc(props.headline)}</div>
                </div>
            `;
        },

        /**
         * Read a CSS custom property value from the root element.
         */
        cssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        destroy() {
            window.removeEventListener('flood-alerts-updated', this._alertsListener);
            window.removeEventListener('flood-alert-focus', this._focusListener);
            window.removeEventListener('flood-alerts-map-reset', this._resetListener);
            window.removeEventListener('flood-alerts-state-zoom', this._stateZoomListener);
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        },
    }));

});