<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-slate-800">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Insights</h1>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Sales trends, product performance, and inventory analytics.</p>
                </div>
                <Link href="/owner/dashboard" class="text-sm font-bold text-cyan-700 hover:text-cyan-800 hover:underline decoration-cyan-300 underline-offset-4">&larr; Back to Dashboard</Link>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Unread Alerts</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ insights.counts.unread_alerts }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-rose-200 shadow-sm p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Critical Alerts</p>
                    <p class="text-2xl font-extrabold text-rose-600 mt-1 tabular-nums">{{ insights.counts.critical_alerts }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Reorders</p>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1 tabular-nums">{{ insights.counts.pending_recommendations }}</p>
                </div>
            </div>

            <!-- Demand Forecast -->
            <section class="mb-8">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="font-bold text-slate-900">{{ analytics.demand_forecast?.mode === 'yoy' ? 'Sales History — Past 12 Months' : 'Expected Sales — This Month' }}</h2>
                            <p class="text-xs font-medium text-slate-500 mt-1">{{ analytics.demand_forecast?.value_label }}</p>
                        </div>
                        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-inner">
                            <button type="button" class="px-4 py-2 text-xs font-bold rounded-lg transition-all" :class="trendScope === 'mtd' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'" @click="setTrendScope('mtd')">This Month</button>
                            <button type="button" class="px-4 py-2 text-xs font-bold rounded-lg transition-all" :class="trendScope === 'yoy' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'" @click="setTrendScope('yoy')">vs. Last Year</button>
                        </div>
                    </div>
                    <div v-show="analytics.demand_forecast?.labels?.length" class="h-52 sm:h-72 -mx-1 px-1 min-w-0">
                        <canvas ref="demandForecastRef" />
                    </div>
                    <div v-show="!analytics.demand_forecast?.labels?.length" class="h-52 sm:h-72 flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50">
                        <p class="text-sm font-medium text-slate-500">Not enough data to predict sales yet.</p>
                    </div>
                    <p class="text-[11px] font-medium text-slate-400 mt-4 text-center">Solid: Actual Sales | Dashed: Projected</p>
                </div>
            </section>

            <!-- Heatmap + Gauge Row -->
            <section class="grid xl:grid-cols-3 gap-6 mb-8">
                <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden flex flex-col">
                    <div class="mb-6">
                        <h2 class="font-bold text-slate-900">Fastest Moving Products</h2>
                        <p class="text-xs font-medium text-slate-500 mt-1">Weekly sales activity for your top sellers.</p>
                    </div>
                    <div v-if="heatmap.rows?.length" class="overflow-x-auto flex-1">
                        <div class="min-w-[520px]">
                            <div class="grid gap-1.5" :style="{ gridTemplateColumns: `minmax(140px,1.2fr) repeat(${heatmap.week_labels.length}, minmax(36px,1fr))` }">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider py-2">Product</div>
                                <div v-for="(wl, wi) in heatmap.week_labels" :key="wi" class="text-[10px] font-bold text-slate-400 uppercase tracking-wider py-2 text-center">{{ wl }}</div>
                                <template v-for="row in heatmap.rows" :key="row.product_id">
                                    <div class="text-xs font-semibold text-slate-700 py-2 truncate pr-2">{{ row.name }}</div>
                                    <div v-for="(cell, ci) in row.cells" :key="ci" class="h-8 rounded-md border border-slate-900/5 hover:border-slate-400 hover:scale-105 hover:shadow-sm transition-all duration-200" :style="{ background: heatColor(cell) }" :title="`${row.name}: ${row.units[ci]} units`" />
                                </template>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex-1 flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50 py-10">
                        <p class="text-sm font-medium text-slate-500">No recent sales to display.</p>
                    </div>
                    <div v-if="heatmap.rows?.length" class="flex items-center justify-end gap-3 mt-6 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <span class="inline-block h-2 w-4 rounded-sm" :style="{ background: heatColor(0.15) }" /> Cold
                        <span class="inline-block h-2 w-4 rounded-sm" :style="{ background: heatColor(0.5) }" /> Mid
                        <span class="inline-block h-2 w-4 rounded-sm" :style="{ background: heatColor(1) }" /> Hot
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center justify-center relative overflow-hidden">
                    <div class="absolute inset-0 opacity-[0.03] pointer-events-none" :class="{ 'bg-emerald-500': gauge.zone === 'balanced', 'bg-amber-500': gauge.zone === 'overstock', 'bg-rose-500': gauge.zone === 'understock' }"></div>
                    <h2 class="font-bold text-slate-900 self-start w-full relative z-10">Stock Health</h2>
                    <p class="text-xs font-medium text-slate-500 mt-1 self-start w-full relative z-10">Estimated days until current stock runs out.</p>
                    <div class="relative w-56 h-32 mt-8 z-10">
                        <svg viewBox="0 0 200 120" class="w-full h-full drop-shadow-sm">
                            <defs>
                                <linearGradient id="gaugeTrack" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#fda4af" />
                                    <stop offset="45%" stop-color="#6ee7b7" />
                                    <stop offset="100%" stop-color="#fcd34d" />
                                </linearGradient>
                            </defs>
                            <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="url(#gaugeTrack)" stroke-width="14" stroke-linecap="round" />
                            <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e2e8f0" stroke-width="14" stroke-linecap="round" opacity="0.3" />
                            <g :transform="`rotate(${gaugeRotation} 100 100)`" style="transition: transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);">
                                <line x1="100" y1="100" x2="100" y2="38" stroke="#0f172a" stroke-width="4" stroke-linecap="round" />
                                <circle cx="100" cy="100" r="6" fill="#0f172a" />
                                <circle cx="100" cy="100" r="2" fill="#ffffff" />
                            </g>
                        </svg>
                    </div>
                    <div class="text-center mt-2 relative z-10">
                        <p class="text-sm font-bold text-slate-800">{{ gauge.headline }}</p>
                        <p v-if="gauge.days_cover != null" class="text-xs font-medium text-slate-500 mt-1 bg-slate-50 px-2 py-1 rounded-md inline-block">~{{ gauge.days_cover }} days remaining</p>
                        <p v-else class="text-[10px] font-medium text-slate-400 mt-1">Need more sales data to calculate this.</p>
                    </div>
                </div>
            </section>

            <!-- Orders Over Time + Top Performers -->
            <section class="grid xl:grid-cols-2 gap-6 mb-8">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-full">
                    <div class="flex flex-col sm:flex-row justify-between gap-3 mb-6">
                        <div>
                            <h2 class="font-bold text-slate-900">Orders Over Time</h2>
                            <p class="text-xs font-medium text-slate-500">{{ charts.orders_period_label }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input v-model="ordFrom" type="date" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 focus:ring-cyan-500 py-2" />
                            <span class="text-slate-400 text-xs">–</span>
                            <input v-model="ordTo" type="date" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 focus:ring-cyan-500 py-2" />
                            <button type="button" class="rounded-xl bg-slate-100 text-slate-700 text-xs font-bold px-4 py-2 hover:bg-slate-200 transition-colors" @click="applyOrders">Apply</button>
                        </div>
                    </div>
                    <div v-show="charts.orders_series?.length" class="h-[220px] sm:h-[280px] -mx-1 min-w-0">
                        <canvas ref="dailyChartRef" />
                    </div>
                    <div v-show="!charts.orders_series?.length" class="h-[220px] sm:h-[280px] flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50">
                        <p class="text-sm font-medium text-slate-400">No order data for this period.</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-full">
                    <div class="flex flex-col lg:flex-row justify-between gap-4 mb-6">
                        <div class="min-w-0 pr-4">
                            <h2 class="font-bold text-slate-900 truncate">Top Performers</h2>
                            <p class="text-xs font-medium text-slate-500 mt-1 truncate" :title="charts.top_period_label + ' \u00B7 ' + topMetricDescription">{{ charts.top_period_label }} &middot; {{ topMetricDescription }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <select v-if="canViewFinancials" v-model="topMetric" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 py-2 focus:ring-cyan-500">
                                <option value="revenue">By Revenue</option>
                                <option value="units">By Units Sold</option>
                                <option value="orders">By Total Orders</option>
                            </select>
                            <select v-else v-model="topMetric" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 py-2 focus:ring-cyan-500">
                                <option value="units">By Units Sold</option>
                                <option value="orders">By Total Orders</option>
                            </select>
                            <button type="button" class="rounded-xl bg-slate-100 text-slate-700 text-xs font-bold px-4 py-2 flex items-center gap-1 hover:bg-slate-200 transition-colors" @click="applyTop">Sort</button>
                        </div>
                    </div>
                    <div v-show="charts.top_products?.length" class="h-[220px] sm:h-[280px] -mx-1 min-w-0">
                        <canvas ref="topProductsChartRef" />
                    </div>
                    <div v-show="!charts.top_products?.length" class="h-[220px] sm:h-[280px] flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50">
                        <p class="text-sm font-medium text-slate-400">No performance data available.</p>
                    </div>
                </div>
            </section>

            <!-- DSS Settings + Alerts -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-bold text-slate-900 mb-4">Reorder Recommendations</h2>
                        <div v-if="!insights.recommendations.length" class="text-sm text-slate-500">No current recommendations.</div>
                        <div v-else class="space-y-3">
                            <div v-for="item in insights.recommendations" :key="item.id" class="border border-slate-200 rounded-xl p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ item.product?.name }}</p>
                                        <p class="text-sm text-slate-600">Current: {{ item.current_stock }} | Suggested: {{ item.recommended_quantity }} | {{ item.days_until_stockout }} days until stockout</p>
                                    </div>
                                    <button @click="markActioned(item.id)" class="text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-bold shrink-0 transition-colors">Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-bold text-slate-900 mb-4">Alerts</h2>
                        <div v-if="!insights.alerts.length" class="text-sm text-slate-500">No alerts.</div>
                        <div v-else class="space-y-3 max-h-[460px] overflow-y-auto">
                            <div v-for="alert in insights.alerts" :key="alert.id" class="border border-slate-200 rounded-xl p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ alert.title }}</p>
                                        <p class="text-sm text-slate-600">{{ alert.message }}</p>
                                        <p class="text-xs text-slate-400 mt-1">{{ new Date(alert.created_at).toLocaleString() }}</p>
                                    </div>
                                    <button v-if="!alert.is_read" @click="markRead(alert.id)" class="text-xs px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 font-bold shrink-0 transition-colors">Read</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-bold text-slate-900 mb-4">Alert Settings</h2>
                        <form @submit.prevent="saveSettings" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1">Low Stock Threshold (days)</label>
                                <input v-model.number="settings.low_stock_threshold_days" type="number" min="1" max="90" class="w-full rounded-xl border-slate-300 text-sm py-2.5" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1">Expiry Warning (days)</label>
                                <input v-model.number="settings.expiry_warning_days" type="number" min="1" max="365" class="w-full rounded-xl border-slate-300 text-sm py-2.5" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1">Dead Stock Threshold (days)</label>
                                <input v-model.number="settings.dead_stock_days" type="number" min="1" max="365" class="w-full rounded-xl border-slate-300 text-sm py-2.5" />
                            </div>
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
                                <input v-model="settings.enable_auto_alerts" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                                Enable Auto Alerts
                            </label>
                            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-xl hover:bg-blue-700 font-bold text-sm transition-colors">Save Settings</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-bold text-slate-900 mb-4">Monthly Summary</h2>
                        <div v-if="!insights.analytics" class="text-sm text-slate-500">No analytics yet.</div>
                        <div v-else class="space-y-3 text-sm">
                            <p><span class="text-slate-500">Orders:</span> <strong>{{ insights.analytics.total_orders }}</strong></p>
                            <p><span class="text-slate-500">Revenue:</span> <strong>PHP {{ Number(insights.analytics.total_revenue).toLocaleString() }}</strong></p>
                            <p><span class="text-slate-500">Avg Order Value:</span> <strong>PHP {{ Number(insights.analytics.average_order_value).toLocaleString() }}</strong></p>
                            <div>
                                <p class="text-slate-500 mb-1">Top Products</p>
                                <ul class="list-disc list-inside text-slate-800">
                                    <li v-for="p in (insights.analytics.top_products || [])" :key="`${p.product_id}-${p.name}`">{{ p.name }} ({{ p.sold_qty }})</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

Chart.register(...registerables);
Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
Chart.defaults.color = '#64748b';

const props = defineProps({
    canViewFinancials: { type: Boolean, default: true },
    insights: Object,
    filters: { type: Object, default: () => ({}) },
    analytics: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
});

const trendScope = ref(props.filters?.trend_scope || 'mtd');
const ordFrom = ref(props.filters?.new_orders?.from || '');
const ordTo = ref(props.filters?.new_orders?.to || '');
const topMetric = ref(props.filters?.top_products?.metric || 'revenue');

const settings = reactive({
    low_stock_threshold_days: props.insights.settings.low_stock_threshold_days,
    expiry_warning_days: props.insights.settings.expiry_warning_days,
    dead_stock_days: props.insights.settings.dead_stock_days,
    enable_auto_alerts: props.insights.settings.enable_auto_alerts,
});

const heatmap = computed(() => props.analytics?.velocity_heatmap || { week_labels: [], rows: [] });
const gauge = computed(() => props.analytics?.inventory_gauge || { needle: 50, zone: 'neutral', headline: '', days_cover: null });
const gaugeRotation = computed(() => -90 + (Number(gauge.value.needle) || 0) / 100 * 180);

const topMetricDescription = computed(() => {
    const m = props.charts?.top_metric ?? topMetric.value;
    if (m === 'revenue') return 'By Sales Revenue';
    if (m === 'units') return 'By Units Sold';
    return 'By Total Orders';
});

function heatColor(t) {
    const x = Math.max(0, Math.min(1, t));
    return `hsl(${220 - x * 200} 85% ${45 + x * 15}%)`;
}

function insightsQuery(overrides = {}) {
    return {
        ord_from: overrides.ord_from ?? ordFrom.value,
        ord_to: overrides.ord_to ?? ordTo.value,
        top_from: overrides.top_from ?? props.filters?.top_products?.from,
        top_to: overrides.top_to ?? props.filters?.top_products?.to,
        top_metric: overrides.top_metric ?? topMetric.value,
        trend_scope: overrides.trend_scope ?? trendScope.value,
    };
}

function navigate(params) {
    router.get('/owner/insights', params, { preserveState: true, preserveScroll: true, replace: true });
}

function setTrendScope(scope) {
    trendScope.value = scope;
    navigate(insightsQuery({ trend_scope: scope }));
}

function applyOrders() { navigate(insightsQuery()); }
function applyTop() { navigate(insightsQuery()); }

function saveSettings() {
    router.patch('/owner/insights/settings', settings, { preserveScroll: true });
}
function markRead(id) {
    router.post(`/owner/insights/alerts/${id}/read`, {}, { preserveScroll: true });
}
function markActioned(id) {
    router.post(`/owner/insights/recommendations/${id}/action`, {}, { preserveScroll: true });
}

watch(() => props.filters, (f) => {
    trendScope.value = f?.trend_scope || 'mtd';
    ordFrom.value = f?.new_orders?.from || '';
    ordTo.value = f?.new_orders?.to || '';
    topMetric.value = f?.top_products?.metric || 'revenue';
}, { deep: true });

const demandForecastRef = ref(null);
const dailyChartRef = ref(null);
const topProductsChartRef = ref(null);

let demandForecastChart, dailyChart, topProductsChart;

function buildDemandChart() {
    const el = demandForecastRef.value;
    const df = props.analytics?.demand_forecast;
    if (!el || !df?.labels?.length) return;

    const datasets = [
        { label: df.mode === 'yoy' ? 'Selected window' : 'Actual Sales', data: df.actual, borderColor: '#0891b2', backgroundColor: 'rgba(8, 145, 178, 0.1)', fill: true, tension: 0.4, pointRadius: 2, pointHoverRadius: 5 },
    ];
    if (df.comparison?.length) {
        datasets.push({ label: df.comparison_label || 'Comparison', data: df.comparison, borderColor: '#94a3b8', fill: false, tension: 0.4, pointRadius: 0, pointHoverRadius: 4, borderDash: [5, 5] });
    }
    datasets.push({ label: 'Projected', data: df.projected, borderColor: '#8b5cf6', fill: false, tension: 0.3, pointRadius: 0, pointHoverRadius: 4, borderDash: [6, 4], spanGaps: true });

    if (demandForecastChart) {
        demandForecastChart.data.labels = df.labels;
        demandForecastChart.data.datasets = datasets;
        demandForecastChart.update();
    } else {
        demandForecastChart = new Chart(el, {
            type: 'line',
            data: { labels: df.labels, datasets },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, font: { weight: '600', size: 11 } } }, tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 10, cornerRadius: 8 } },
                scales: { y: { beginAtZero: true, suggestedMax: 10, grid: { color: '#f1f5f9', drawBorder: false } }, x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 12, font: { size: 10 } } } },
            },
        });
    }
}

function buildCharts() {
    nextTick(() => {
        setTimeout(() => {
            buildDemandChart();

            const daily = props.charts?.orders_series || [];
            if (dailyChartRef.value && daily.length) {
                if (dailyChart) {
                    dailyChart.data.labels = daily.map((d) => d.label);
                    dailyChart.data.datasets[0].data = daily.map((d) => d.count);
                    dailyChart.update();
                } else {
                    dailyChart = new Chart(dailyChartRef.value, {
                        type: 'line',
                        data: { labels: daily.map((d) => d.label), datasets: [{ label: 'Orders', data: daily.map((d) => d.count), borderColor: '#0f172a', backgroundColor: 'rgba(15, 23, 42, 0.05)', fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 5 }] },
                        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 10, cornerRadius: 8 } }, scales: { y: { beginAtZero: true, suggestedMax: 5, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9', drawBorder: false } }, x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 8, font: { size: 10 } } } } },
                    });
                }
            }

            const top = props.charts?.top_products || [];
            if (topProductsChartRef.value && top.length) {
                const labels = top.map((p) => (p.name.length > 22 ? `${p.name.slice(0, 20)}\u2026` : p.name));
                const data = top.map((p) => p.value);
                if (topProductsChart) {
                    topProductsChart.data.labels = labels;
                    topProductsChart.data.datasets[0].data = data;
                    topProductsChart.update();
                } else {
                    topProductsChart = new Chart(topProductsChartRef.value, {
                        type: 'bar',
                        data: { labels, datasets: [{ data, backgroundColor: 'rgba(6, 182, 212, 0.8)', hoverBackgroundColor: 'rgba(8, 145, 178, 1)', borderRadius: 4, barPercentage: 0.6 }] },
                        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 10, cornerRadius: 8, callbacks: { label(ctx) { return top[ctx.dataIndex]?.value_label || String(ctx.raw); } } } }, scales: { x: { beginAtZero: true, suggestedMax: 10, grid: { color: '#f1f5f9', drawBorder: false }, ticks: { font: { size: 10 } } }, y: { grid: { display: false }, ticks: { font: { weight: '600', size: 11, color: '#334155' } } } } },
                    });
                }
            }
        }, 50);
    });
}

onMounted(buildCharts);
watch(() => [props.charts, props.analytics, props.canViewFinancials], buildCharts, { deep: true });

onBeforeUnmount(() => {
    if (demandForecastChart) demandForecastChart.destroy();
    if (dailyChart) dailyChart.destroy();
    if (topProductsChart) topProductsChart.destroy();
});
</script>
