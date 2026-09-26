<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-ink">Orders</h1>
                    <p class="text-ink-soft mt-1">All platform orders across every shop.</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Order #, customer, or shop..."
                        class="w-full rounded-control border border-line py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-brand focus:border-transparent"
                        @keydown.enter="applySearch"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                            ? 'bg-brand text-white border-brand'
                            : 'bg-white text-ink-soft border-line hover:border-brand-soft'
                    ]"
                >
                    {{ s.label }}
                    <span class="ml-1 opacity-70">{{ s.count }}</span>
                </Link>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-mist text-xs text-ink-soft  ">
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
                        <tbody class="divide-y divide-line">
                            <tr v-for="o in orders.data" :key="o.id" class="hover:bg-mist/50">
                                <td class="px-6 py-3 font-mono text-xs font-semibold text-ink">{{ o.order_number }}</td>
                                <td class="px-6 py-3 text-ink">{{ o.customer?.name ?? '—' }}</td>
                                <td class="px-6 py-3 text-ink">{{ o.distributor?.company_name ?? '—' }}</td>
                                <td class="px-6 py-3 font-semibold text-ink">{{ currency(o.total_amount) }}</td>
                                <td class="px-6 py-3">
                                    <span :class="statusClasses(o.status)" class="px-2 py-0.5 rounded-full text-xs font-bold ">{{ o.status }}</span>
                                </td>
                                <td class="px-6 py-3 text-ink-faint text-xs">{{ formatDate(o.created_at) }}</td>
                                <td class="px-6 py-3">
                                    <Link :href="`/admin/orders/${o.order_number}`" class="text-xs text-brand font-semibold hover:text-brand-dark">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!orders.data?.length" class="p-12 text-center text-ink-faint text-sm">No orders match your filters.</div>

                <!-- Pagination -->
                <div v-if="orders.links?.length > 3" class="px-6 py-4 border-t border-line flex justify-center gap-1">
                    <Link
                        v-for="link in orders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 rounded-control text-xs font-semibold transition',
                            link.active ? 'bg-brand text-white' : link.url ? 'bg-mist text-ink-soft hover:bg-line' : 'text-ink-faint cursor-not-allowed'
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
    'bg-brand-tint text-brand-dark': status === 'accepted' || status === 'packed',
    'bg-brand-tint text-brand-dark': status === 'shipped',
    'bg-brand-tint text-brand-dark': status === 'delivered' || status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled' || status === 'rejected',
});
</script>
