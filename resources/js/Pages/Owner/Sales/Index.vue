<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Sales Records</h1>
                    <p class="text-gray-600 mt-1">Revenue overview and order history</p>
                </div>
                <button @click="exportCSV" class="flex items-center gap-2 px-5 py-2.5 bg-gray-800 hover:bg-gray-900 text-white rounded-xl font-semibold text-sm transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </button>
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-blue-100 text-sm font-medium">Total Revenue</p>
                        <div class="bg-white/20 rounded-lg p-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black">₱{{ Number(stats.total_revenue).toLocaleString() }}</p>
                    <p class="text-blue-200 text-xs mt-1">{{ filters.date_from || filters.date_to ? 'Filtered period' : 'All time' }}</p>
                </div>

                <!-- Orders -->
                <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-gray-500 text-sm font-medium">Total Orders</p>
                        <div class="bg-purple-50 rounded-lg p-2">
                            <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-gray-900">{{ stats.total_orders.toLocaleString() }}</p>
                    <p class="text-gray-400 text-xs mt-1">Delivered orders</p>
                </div>

                <!-- Avg Order Value -->
                <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-gray-500 text-sm font-medium">Avg Order Value</p>
                        <div class="bg-green-50 rounded-lg p-2">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-gray-900">₱{{ Number(stats.avg_order_value).toLocaleString() }}</p>
                    <p class="text-gray-400 text-xs mt-1">Per order</p>
                </div>

                <!-- This Month vs Last -->
                <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-gray-500 text-sm font-medium">This Month</p>
                        <span v-if="stats.revenue_growth !== null"
                            :class="stats.revenue_growth >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                        >
                            {{ stats.revenue_growth >= 0 ? '▲' : '▼' }} {{ Math.abs(stats.revenue_growth) }}%
                        </span>
                    </div>
                    <p class="text-3xl font-black text-gray-900">₱{{ Number(stats.this_month).toLocaleString() }}</p>
                    <p class="text-gray-400 text-xs mt-1">vs ₱{{ Number(stats.last_month).toLocaleString() }} last month</p>
                </div>
            </div>

            <!-- Payment Status Breakdown -->
            <div v-if="Object.keys(payment_breakdown).length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div v-for="(data, status) in payment_breakdown" :key="status"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
                    <span :class="{
                        'text-yellow-600 bg-yellow-50': status === 'unpaid',
                        'text-green-600 bg-green-50':  status === 'paid',
                        'text-blue-600 bg-blue-50':    status === 'partial',
                        'text-red-600 bg-red-50':      status === 'overdue',
                    }" class="text-xs font-bold px-2 py-0.5 rounded-full capitalize">{{ status }}</span>
                    <p class="text-2xl font-black text-gray-900 mt-2">{{ data.count }}</p>
                    <p class="text-xs text-gray-500">₱{{ Number(data.total).toLocaleString() }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date From</label>
                    <input v-model="filterDateFrom" type="date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date To</label>
                    <input v-model="filterDateTo" type="date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select v-model="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input v-model="filterSearch" type="text" placeholder="Order # or customer name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"/>
                </div>
                <button @click="applyFilters" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
                    Apply
                </button>
                <button @click="clearFilters" class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition">
                    Clear
                </button>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden" ref="tableRef">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Products</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Order Status</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Payment</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="7" class="text-center py-16 text-gray-400">
                                    <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    No orders found
                                </td>
                            </tr>
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition group">
                                <td class="px-6 py-4">
                                    <Link :href="`/owner/orders/${order.id}`"
                                        class="font-mono text-sm font-bold text-blue-600 hover:text-blue-700 hover:underline">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900 text-sm">{{ order.customer?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ order.customer?.email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-700 max-w-xs truncate">
                                        {{ order.items?.map(i => i.product?.name).join(', ') }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ order.items?.length }} item(s)</p>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900">
                                    ₱{{ Number(order.total_amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-yellow-100 text-yellow-800':  order.status === 'pending',
                                        'bg-blue-100 text-blue-800':      order.status === 'approved',
                                        'bg-purple-100 text-purple-800':  order.status === 'packed',
                                        'bg-indigo-100 text-indigo-800':  order.status === 'shipped',
                                        'bg-green-100 text-green-800':    order.status === 'delivered',
                                        'bg-red-100 text-red-800':        ['cancelled','rejected'].includes(order.status),
                                    }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="order.invoice" :class="{
                                        'bg-yellow-100 text-yellow-800': order.invoice.status === 'unpaid',
                                        'bg-green-100 text-green-800':  order.invoice.status === 'paid',
                                        'bg-blue-100 text-blue-800':    order.invoice.status === 'partial',
                                        'bg-red-100 text-red-800':      order.invoice.status === 'overdue',
                                    }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                        {{ order.invoice.status }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4 text-center text-xs text-gray-500">
                                    {{ new Date(order.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2">
                    <Link v-for="link in orders.links" :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded-lg text-sm transition',
                            link.active ? 'bg-blue-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-100',
                            !link.url ? 'opacity-40 cursor-not-allowed' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    orders:            Object,
    filters:           Object,
    stats:             Object,
    payment_breakdown: Object,
});

const filterDateFrom = ref(props.filters?.date_from || '');
const filterDateTo   = ref(props.filters?.date_to   || '');
const filterStatus   = ref(props.filters?.status    || '');
const filterSearch   = ref(props.filters?.search    || '');
const tableRef       = ref(null);

const applyFilters = () => {
    router.get('/owner/sales', {
        date_from: filterDateFrom.value,
        date_to:   filterDateTo.value,
        status:    filterStatus.value,
        search:    filterSearch.value,
    }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filterDateFrom.value = '';
    filterDateTo.value   = '';
    filterStatus.value   = '';
    filterSearch.value   = '';
    applyFilters();
};

// Client-side CSV export from the currently visible table data
const exportCSV = () => {
    const rows = props.orders.data.map(o => [
        o.order_number,
        o.customer?.name,
        o.customer?.email,
        o.items?.map(i => i.product?.name).join(' | '),
        o.items?.length,
        o.total_amount,
        o.status,
        o.invoice?.status || '',
        new Date(o.created_at).toLocaleDateString('en-PH'),
    ]);

    const header = ['Order #','Customer','Email','Products','Items','Total','Order Status','Payment Status','Date'];
    const csv = [header, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `sales-${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
};
</script>
