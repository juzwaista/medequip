<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">DSS Center</h1>
                    <p class="text-gray-600 mt-1">Inventory and sales insights to help you make better decisions.</p>
                </div>
                <Link href="/owner/dashboard" class="text-blue-600 hover:text-blue-700 font-medium">Back to Dashboard</Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-sm text-gray-600">Unread Alerts</p>
                    <p class="text-2xl font-bold text-gray-900">{{ insights.counts.unread_alerts }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-sm text-gray-600">Critical Alerts</p>
                    <p class="text-2xl font-bold text-red-600">{{ insights.counts.critical_alerts }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <p class="text-sm text-gray-600">Pending Reorders</p>
                    <p class="text-2xl font-bold text-orange-600">{{ insights.counts.pending_recommendations }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Reorder Recommendations</h2>
                        <div v-if="insights.recommendations.length === 0" class="text-sm text-gray-500">No current recommendations.</div>
                        <div v-else class="space-y-3">
                            <div v-for="item in insights.recommendations" :key="item.id" class="border rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ item.product?.name }}</p>
                                        <p class="text-sm text-gray-600">
                                            Current: {{ item.current_stock }} | Suggested: {{ item.recommended_quantity }} | Days until stockout: {{ item.days_until_stockout }}
                                        </p>
                                    </div>
                                    <button @click="markActioned(item.id)" class="text-xs px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                                        Mark Actioned
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Alerts</h2>
                        <div v-if="insights.alerts.length === 0" class="text-sm text-gray-500">No alerts.</div>
                        <div v-else class="space-y-3 max-h-[460px] overflow-y-auto">
                            <div v-for="alert in insights.alerts" :key="alert.id" class="border rounded-lg p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ alert.title }}</p>
                                        <p class="text-sm text-gray-700">{{ alert.message }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ new Date(alert.created_at).toLocaleString() }}</p>
                                    </div>
                                    <button v-if="!alert.is_read" @click="markRead(alert.id)" class="text-xs px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700">
                                        Mark Read
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">DSS Settings</h2>
                        <form @submit.prevent="saveSettings" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold (days)</label>
                                <input v-model.number="settings.low_stock_threshold_days" type="number" min="1" max="90" class="w-full rounded border-gray-300" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Warning (days)</label>
                                <input v-model.number="settings.expiry_warning_days" type="number" min="1" max="365" class="w-full rounded border-gray-300" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dead Stock Threshold (days)</label>
                                <input v-model.number="settings.dead_stock_days" type="number" min="1" max="365" class="w-full rounded border-gray-300" />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="settings.enable_auto_alerts" type="checkbox" />
                                Enable Auto Alerts
                            </label>
                            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700">Save Settings</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Sales Analytics (Monthly)</h2>
                        <div v-if="!insights.analytics" class="text-sm text-gray-500">No analytics yet.</div>
                        <div v-else class="space-y-3 text-sm">
                            <p><span class="text-gray-600">Orders:</span> <strong>{{ insights.analytics.total_orders }}</strong></p>
                            <p><span class="text-gray-600">Revenue:</span> <strong>PHP {{ Number(insights.analytics.total_revenue).toLocaleString() }}</strong></p>
                            <p><span class="text-gray-600">Avg Order Value:</span> <strong>PHP {{ Number(insights.analytics.average_order_value).toLocaleString() }}</strong></p>
                            <div>
                                <p class="text-gray-600 mb-1">Top Products</p>
                                <ul class="list-disc list-inside text-gray-800">
                                    <li v-for="p in (insights.analytics.top_products || [])" :key="`${p.product_id}-${p.name}`">
                                        {{ p.name }} ({{ p.sold_qty }})
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    insights: Object,
});

const settings = reactive({
    low_stock_threshold_days: props.insights.settings.low_stock_threshold_days,
    expiry_warning_days: props.insights.settings.expiry_warning_days,
    dead_stock_days: props.insights.settings.dead_stock_days,
    enable_auto_alerts: props.insights.settings.enable_auto_alerts,
});

const saveSettings = () => {
    router.patch('/owner/dss/settings', settings, { preserveScroll: true });
};

const markRead = (id) => {
    router.post(`/owner/dss/alerts/${id}/read`, {}, { preserveScroll: true });
};

const markActioned = (id) => {
    router.post(`/owner/dss/recommendations/${id}/action`, {}, { preserveScroll: true });
};
</script>

