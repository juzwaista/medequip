<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-ink">Sales Records</h1>
                    <p class="text-ink-soft mt-1">Revenue overview and order history</p>
                </div>
                <button @click="exportCSV" class="flex items-center gap-2 px-5 py-2.5 bg-ink hover:bg-ink text-white rounded-card font-semibold text-sm transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </button>
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-brand to-brand-dark rounded-card p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-white/80 text-sm font-medium">Total Revenue</p>
                        <div class="bg-white/20 rounded-control p-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-semibold">₱{{ Number(stats.total_revenue).toLocaleString() }}</p>
                    <p class="text-white/70 text-xs mt-1">{{ filters.date_from || filters.date_to ? 'Filtered period' : 'All time' }}</p>
                </div>

                <!-- Orders -->
                <div class="bg-white rounded-card p-6 shadow-md border border-line">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-ink-soft text-sm font-medium">Total Orders</p>
                        <div class="bg-brand-tint rounded-control p-2">
                            <svg class="h-5 w-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-semibold text-ink">{{ stats.total_orders.toLocaleString() }}</p>
                    <p class="text-ink-faint text-xs mt-1">Delivered orders</p>
                </div>

                <!-- Pending Payment Release -->
                <div class="bg-white rounded-card p-6 shadow-md border border-line">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-orange-500 text-sm font-medium">Pending Release</p>
                        <div class="bg-orange-50 rounded-control p-2">
                            <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-semibold text-ink">₱{{ Number(stats.escrow_held).toLocaleString() }}</p>
                    <p class="text-ink-faint text-xs mt-1">Pending delivery completion</p>
                </div>

                <!-- Avg Order Value -->
                <div class="bg-white rounded-card p-6 shadow-md border border-line">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-ink-soft text-sm font-medium">Avg Order Value</p>
                        <div class="bg-brand-tint rounded-control p-2">
                            <svg class="h-5 w-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-semibold text-ink">₱{{ Number(stats.avg_order_value).toLocaleString() }}</p>
                    <p class="text-ink-faint text-xs mt-1">Per order</p>
                </div>

                <!-- This Month vs Last -->
                <div class="bg-white rounded-card p-6 shadow-md border border-line">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-ink-soft text-sm font-medium">This Month</p>
                        <span v-if="stats.revenue_growth !== null"
                            :class="stats.revenue_growth >= 0 ? 'bg-brand-tint text-brand-dark' : 'bg-red-50 text-red-700'"
                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                        >
                            {{ stats.revenue_growth >= 0 ? '▲' : '▼' }} {{ Math.abs(stats.revenue_growth) }}%
                        </span>
                    </div>
                    <p class="text-3xl font-semibold text-ink">₱{{ Number(stats.this_month).toLocaleString() }}</p>
                    <p class="text-ink-faint text-xs mt-1">vs ₱{{ Number(stats.last_month).toLocaleString() }} last month</p>
                </div>
            </div>

            <!-- Payment Status Breakdown -->
            <div v-if="Object.keys(payment_breakdown).length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div v-for="(data, status) in payment_breakdown" :key="status"
                    class="bg-white rounded-card shadow-sm border border-line p-4 text-center">
                    <span :class="{
                        'text-yellow-600 bg-yellow-50': status === 'unpaid',
                        'text-brand bg-brand-tint':  status === 'paid',
                        'text-brand bg-brand-tint':    status === 'partial',
                        'text-red-600 bg-red-50':      status === 'overdue',
                    }" class="text-xs font-bold px-2 py-0.5 rounded-full capitalize">{{ status }}</span>
                    <p class="text-2xl font-semibold text-ink mt-2">{{ data.count }}</p>
                    <p class="text-xs text-ink-soft">₱{{ Number(data.total).toLocaleString() }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-card shadow-sm border border-line p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Date From</label>
                    <input v-model="filterDateFrom" type="date" class="px-3 py-2 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Date To</label>
                    <input v-model="filterDateTo" type="date" class="px-3 py-2 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Status</label>
                    <select v-model="filterStatus" class="px-3 py-2 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-ink-soft mb-1">Search</label>
                    <input v-model="filterSearch" type="text" placeholder="Order # or customer name"
                        class="w-full px-3 py-2 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"/>
                </div>
                <button @click="applyFilters" class="px-5 py-2 bg-brand hover:bg-brand-dark text-white rounded-control text-sm font-semibold transition">
                    Apply
                </button>
                <button @click="clearFilters" class="px-5 py-2 border border-line text-ink rounded-control text-sm font-semibold hover:bg-mist transition">
                    Clear
                </button>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-card shadow-md overflow-hidden" ref="tableRef">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-mist border-b border-line">
                                <th class="text-left px-6 py-4 text-xs font-bold text-ink-soft  ">Order</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-ink-soft  ">Invoice #</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-ink-soft  ">Customer</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-ink-soft  ">Products</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-ink-soft  ">Total</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-ink-soft  ">Order Status</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-ink-soft  ">Payment</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-ink-soft  ">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="7" class="text-center py-16 text-ink-faint">
                                    <svg class="h-12 w-12 mx-auto mb-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    No orders found
                                </td>
                            </tr>
                            <template v-for="order in orders.data" :key="order.id">
                                <tr class="hover:bg-mist transition group cursor-pointer" @click="toggleRow(order.id)">
                                    <td class="px-6 py-4 relative">
                                        <div class="flex items-center gap-2">
                                            <svg :class="['h-4 w-4 text-ink-faint transition-transform', expandedRows.includes(order.id) ? 'rotate-90 text-brand' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                            <Link :href="`/owner/orders/${order.order_number}`" @click.stop
                                                class="font-mono text-sm font-bold text-brand hover:text-brand-dark hover:underline">
                                                {{ order.order_number }}
                                            </Link>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-sm text-ink-soft">
                                        {{ order.invoice?.invoice_number || '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-ink text-sm">{{ order.customer?.name }}</p>
                                        <p class="text-xs text-ink-soft">{{ order.customer?.email }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-ink max-w-xs truncate">
                                            {{ order.items?.map(i => i.product?.name).join(', ') }}
                                        </p>
                                        <p class="text-xs text-ink-faint">{{ order.items?.length }} item(s)</p>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-ink">
                                        ₱{{ Number(order.total_amount).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="{
                                            'bg-yellow-100 text-yellow-800':  order.status === 'pending',
                                            'bg-brand-tint text-brand-dark':      order.status === 'approved',
                                            'bg-brand-tint text-brand-dark':  order.status === 'packed',
                                            'bg-brand-tint text-brand-dark':  order.status === 'shipped',
                                            'bg-brand-tint text-brand-dark':    order.status === 'delivered',
                                            'bg-red-100 text-red-800':        ['cancelled','rejected'].includes(order.status),
                                        }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="order.invoice" :class="{
                                            'bg-yellow-100 text-yellow-800': order.invoice.status === 'unpaid',
                                            'bg-brand-tint text-brand-dark':  order.invoice.status === 'paid',
                                            'bg-brand-tint text-brand-dark':    order.invoice.status === 'partial',
                                            'bg-red-100 text-red-800':      order.invoice.status === 'overdue',
                                        }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                            {{ order.invoice.status }}
                                        </span>
                                        <span v-else class="text-xs text-ink-faint">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs text-ink-soft">
                                        {{ new Date(order.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                    </td>
                                </tr>

                                <!-- Expanded Row Details -->
                                <tr v-if="expandedRows.includes(order.id)" class="bg-mist border-b border-line shadow-inner block w-full table-row">
                                    <td colspan="8" class="px-8 py-5">
                                        <div class="grid md:grid-cols-2 gap-8">
                                            <!-- Invoice Breakdown -->
                                            <div>
                                                <h4 class="text-sm font-bold text-ink   mb-3 border-b pb-2">Invoice Breakdown</h4>
                                                <div class="space-y-2 text-sm text-ink-soft">
                                                    <div class="flex justify-between">
                                                        <span>Items Subtotal</span>
                                                        <span class="font-medium text-ink">₱{{ (order.total_amount - (order.shipping_fee || 0)).toLocaleString() }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>Shipping Fee</span>
                                                        <span class="font-medium text-ink">₱{{ Number(order.shipping_fee || 0).toLocaleString() }}</span>
                                                    </div>
                                                    <div class="flex justify-between pt-2 border-t">
                                                        <span class="font-semibold text-ink">Gross Total</span>
                                                        <span class="font-bold text-ink">₱{{ Number(order.total_amount).toLocaleString() }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Payout Details (if any valid payments) -->
                                            <div v-if="order.invoice?.payments?.length > 0">
                                                <h4 class="text-sm font-bold text-ink   mb-3 border-b pb-2">Platform Settlement</h4>
                                                <div v-for="payment in order.invoice.payments" :key="payment.id" class="space-y-2 text-sm text-ink-soft mb-4 bg-white p-3 rounded shadow-sm border border-line">
                                                    <div class="flex justify-between">
                                                        <span>Status</span>
                                                        <span :class="{'text-brand font-bold': payment.status === 'verified', 'text-yellow-600': payment.status === 'pending'}" class="capitalize">{{ payment.status }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>Platform Fee</span>
                                                        <span class="font-medium text-red-600">- ₱{{ Number(payment.platform_fee_amount || 0).toLocaleString() }}</span>
                                                    </div>
                                                    <div class="flex justify-between pt-2 border-t font-semibold">
                                                        <span class="text-brand-dark">Net Seller Payout</span>
                                                        <span class="font-bold text-brand-dark">₱{{ Number(payment.net_seller_amount || 0).toLocaleString() }}</span>
                                                    </div>
                                                    <p class="text-xs text-ink-faint mt-2 text-right italic font-normal">For COD orders, the platform fee is deducted from your wallet balance.</p>
                                                </div>
                                            </div>
                                            <div v-else class="flex items-center text-sm text-ink-soft bg-white p-3 rounded border border-line">
                                                No payments recorded against this invoice yet.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.last_page > 1" class="px-6 py-4 border-t border-line flex justify-center gap-2">
                    <Link v-for="link in orders.links" :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded-control text-sm transition',
                            link.active ? 'bg-brand text-white font-bold' : 'text-ink-soft hover:bg-mist',
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
const expandedRows   = ref([]);

const toggleRow = (orderId) => {
    const index = expandedRows.value.indexOf(orderId);
    if (index === -1) {
        expandedRows.value.push(orderId);
    } else {
        expandedRows.value.splice(index, 1);
    }
};

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
        o.invoice?.invoice_number,
        o.customer?.name,
        o.customer?.email,
        o.items?.map(i => i.product?.name).join(' | '),
        o.items?.length,
        o.total_amount,
        o.status,
        o.invoice?.status || '',
        new Date(o.created_at).toLocaleDateString('en-PH'),
    ]);

    const header = ['Order #','Invoice #','Customer','Email','Products','Items','Total','Order Status','Payment Status','Date'];
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
