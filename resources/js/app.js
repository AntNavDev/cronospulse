import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

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

window.Alpine = Alpine;
Alpine.start();