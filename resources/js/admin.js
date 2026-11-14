import ApexCharts from 'apexcharts';

// Biểu đồ doanh thu: mọi phần tử [data-chart] chứa {labels, series: {web, pos, shopee}}.
const vnd = new Intl.NumberFormat('vi-VN');
const short = (v) => (v >= 1e6 ? `${vnd.format(Math.round(v / 1e5) / 10)}tr` : v >= 1e3 ? `${vnd.format(Math.round(v / 1e3))}k` : vnd.format(v));

function renderCharts() {
    document.querySelectorAll('[data-chart]').forEach((el) => {
        if (el._chart) return;
        const data = JSON.parse(el.dataset.chart);
        const accent = getComputedStyle(document.documentElement).getPropertyValue('--color-accent').trim() || '#2B3360';
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        el._chart = new ApexCharts(el, {
            chart: { type: 'bar', stacked: true, height: '100%', toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: !reduce } },
            series: [
                { name: 'Web', data: data.series.web },
                { name: 'Tại quầy', data: data.series.pos },
                { name: 'Shopee', data: data.series.shopee },
            ],
            colors: [accent, '#C9A96E', '#EE4D2D'],
            xaxis: { categories: data.labels, labels: { rotate: 0, hideOverlappingLabels: true } },
            yaxis: { labels: { formatter: short } },
            tooltip: { y: { formatter: (v) => `${vnd.format(v)}đ` } },
            dataLabels: { enabled: false },
            legend: { position: 'top', horizontalAlign: 'left' },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 2 } },
            grid: { borderColor: '#E4E4E7', strokeDashArray: 3 },
        });
        el._chart.render();
    });
}

document.addEventListener('DOMContentLoaded', renderCharts);
document.addEventListener('livewire:init', () => {
    // Đổi khoảng thời gian: Livewire thay phần tử biểu đồ (wire:key mới), vẽ lại cái mới.
    window.Livewire.hook('morphed', () => queueMicrotask(renderCharts));
});
