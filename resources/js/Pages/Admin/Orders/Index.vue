<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
                    <p class="text-gray-500 mt-1">All platform orders across every shop.</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Order #, customer, or shop..."
                        class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        @keydown.enter="applySearch"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="flex flex-wrap gap-2 mb-6">
                <Link
                    v-for="s in statusOptions"
                    :key="s.key"
                    :href="filterHref(s.key)"
                    :class="[
                        'px-3 py-1.5 rounded-full text-xs font-bold transition border',
                        filters.status === s.key
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300'
                    ]"
                >
                    {{ s.label }}
                    <span class="ml-1 opacity-70">{{ s.count }}</span>
                </Link>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Order #</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Shop</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="o in orders.data" :key="o.id" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3 font-mono text-xs font-semibold text-gray-700">{{ o.order_number }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ o.customer?.name ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ o.distributor?.company_name ?? '—' }}</td>
                                <td class="px-6 py-3 font-semibold text-gray-900">{{ currency(o.total_amount) }}</td>
                                <td class="px-6 py-3">
                                    <span :class="statusClasses(o.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ o.status }}</span>
                                </td>
                                <td class="px-6 py-3 text-gray-400 text-xs">{{ formatDate(o.created_at) }}</td>
                                <td class="px-6 py-3">
                                    <Link :href="`/admin/orders/${o.id}`" class="text-xs text-blue-600 font-semibold hover:text-blue-800">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!orders.data?.length" class="p-12 text-center text-gray-400 text-sm">No orders match your filters.</div>

                <!-- Pagination -->
                <div v-if="orders.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex justify-center gap-1">
                    <Link
                        v-for="link in orders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                            link.active ? 'bg-blue-600 text-white' : link.url ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'text-gray-300 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    orders: Object,
    statusCounts: Object,
    filters: Object,
});

const searchInput = ref(props.filters?.search || '');

const statusOptions = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'accepted', label: 'Accepted' },
    { key: 'packed', label: 'Packed' },
    { key: 'shipped', label: 'Shipped' },
    { key: 'delivered', label: 'Delivered' },
    { key: 'completed', label: 'Completed' },
    { key: 'cancelled', label: 'Cancelled' },
].map(s => ({ ...s, count: props.statusCounts?.[s.key] ?? 0 }));

const filterHref = (status) => {
    const params = new URLSearchParams({ status, search: searchInput.value });
    return `/admin/orders?${params.toString()}`;
};

const applySearch = () => {
    router.get('/admin/orders', {
        search: searchInput.value,
        status: props.filters?.status || 'all',
    }, { preserveState: true, replace: true });
};

const currency = (val) => '₱' + Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

const statusClasses = (status) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-blue-100 text-blue-800': status === 'accepted' || status === 'packed',
    'bg-indigo-100 text-indigo-800': status === 'shipped',
    'bg-emerald-100 text-emerald-800': status === 'delivered' || status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled' || status === 'rejected',
});
</script>
