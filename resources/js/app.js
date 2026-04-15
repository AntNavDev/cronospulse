import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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
Alpine.data('lineChart', (chartData = { labels: [], datasets: [] }) => ({
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
 *   map-radius-updated → { detail: { radius } }  (radius in miles)
 */
Alpine.data('leafletMap', ({ elementId, centerLat = 39.5, centerLng = -98.35, zoom = 4 }) => ({
    map: null,
    circle: null,
    radiusMeters: 50 * 1609.34,
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

            window.dispatchEvent(new CustomEvent('map-clicked', {
                detail: { lat, lng },
            }));
        });

        // Listen for radius changes dispatched from the page
        this._radiusListener = (e) => {
            const miles = Number(e.detail.radius);
            if (!miles || miles < 1) return;
            this.radiusMeters = miles * 1609.34;
            if (this.circle) {
                this.circle.setRadius(this.radiusMeters);
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

window.Alpine = Alpine;
Alpine.start();