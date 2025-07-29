// resources/js/app.js
import './bootstrap';

// Bootstrap JS (Tabler dependency)
import 'bootstrap';

// Tabler Core JS
import '@tabler/core/dist/js/tabler.min.js';

// Chart libraries
import Chart from 'chart.js/auto';
import ApexCharts from 'apexcharts';

// Global olarak erişilebilir yap
window.Chart = Chart;
window.ApexCharts = ApexCharts;

console.log('Tabler and charts loaded successfully!');