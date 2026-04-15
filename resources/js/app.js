import Collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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
     *   map-clicked  → { detail: { lat, lng } }
     *
     * Browser events listened for:
     *   map-radius-updated → { detail: { meters } }  (radius pre-converted to metres)
     */
    window.Alpine.data('leafletMap', ({ elementId, centerLat = 39.5, centerLng = -98.35, zoom = 4 }) => ({
        map: null,
        circle: null,
        radiusMeters: 50 * 1000, // default 50 km in metres
        _radiusListener: null,

        init() {
            this.map = L.map(elementId).setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
            }).addTo(this.map);

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

            window.addEventListener('map-radius-updated', this._radiusListener);
        },

        destroy() {
            if (this._radiusListener) {
                window.removeEventListener('map-radius-updated', this._radiusListener);
            }
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        },
    }));

});