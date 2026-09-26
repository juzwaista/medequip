<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/admin/couriers" class="text-ink-soft hover:text-ink">
                    &larr; Back to Couriers
                </Link>
                <h2 class="font-semibold text-xl text-ink leading-tight border-l pl-4 border-line">Platform Deliveries Oversight</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Filters -->
                <div class="bg-white p-4 shadow-sm sm:rounded-control flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-ink">Filter by Status:</span>
                        <select v-model="statusFilter" @change="applyFilter" class="rounded-control border-line shadow-sm focus:border-brand-soft focus:ring focus:ring-brand-soft focus:ring-opacity-50 text-sm">
                            <option value="">All Deliveries</option>
                            <option value="pending">Pending</option>
                            <option value="picked_up">Picked Up</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                <!-- Deliveries List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-control">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-ink-soft">
                            <thead class="text-xs text-ink  bg-mist border-b border-line">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Tracking # / Order</th>
                                    <th scope="col" class="px-6 py-4">Courier Assigned</th>
                                    <th scope="col" class="px-6 py-4">Customer</th>
                                    <th scope="col" class="px-6 py-4 border-l">Status</th>
                                    <th scope="col" class="px-6 py-4 text-right">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="delivery in deliveries.data" :key="delivery.id" class="hover:bg-mist">
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-ink">{{ delivery.tracking_number }}</div>
                                        <div class="text-xs text-ink-soft mt-1">Order #{{ (delivery.order_id || '000').toString().padStart(6, '0') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="delivery.courier" class="font-medium text-ink">
                                            {{ delivery.courier.user.name }}
                                        </div>
                                        <span v-else class="text-xs italic text-ink-faint">Unassigned</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-ink">{{ delivery.order?.customer?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-ink-soft truncate max-w-[200px]" :title="delivery.order?.shipping_address">{{ delivery.order?.shipping_address }}</div>
                                    </td>
                                    <td class="px-6 py-4 border-l">
                                        <span 
                                            :class="{
                                                'bg-mist text-ink': delivery.status === 'pending',
                                                'bg-yellow-100 text-yellow-800': delivery.status === 'picked_up',
                                                'bg-brand-tint text-brand-dark': delivery.status === 'in_transit',
                                                'bg-brand-tint text-brand-dark': delivery.status === 'delivered',
                                                'bg-red-100 text-red-800': delivery.status === 'failed',
                                            }"
                                            class="px-2.5 py-1 rounded-control text-xs font-semibold  "
                                        >
                                            {{ delivery.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-ink-faint text-xs">
                                        {{ new Date(delivery.updated_at).toLocaleString() }}
                                    </td>
                                </tr>
                                <tr v-if="deliveries.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-ink-soft">No deliveries found matching the criteria.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="deliveries.links && deliveries.links.length > 3" class="flex justify-center mt-6">
                    <div class="flex shadow-sm rounded-control bg-white">
                        <template v-for="(link, i) in deliveries.links" :key="i">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 border text-sm font-medium',
                                    link.active ? 'z-10 bg-brand-tint border-brand text-brand' : 'bg-white border-line text-ink-soft hover:bg-mist',
                                    i === 0 ? 'rounded-l-control' : '',
                                    i === deliveries.links.length - 1 ? 'rounded-r-control' : ''
                                ]"
                                v-html="link.label"
                            />
                            <span 
                                v-else
                                :class="[
                                    'px-4 py-2 border text-sm font-medium bg-mist border-line text-ink-faint cursor-not-allowed',
                                    i === 0 ? 'rounded-l-control' : '',
                                    i === deliveries.links.length - 1 ? 'rounded-r-control' : ''
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
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
    deliveries: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || '');

const applyFilter = () => {
    router.get(route('admin.couriers.deliveries'), { status: statusFilter.value }, { preserveState: true, preserveScroll: true });
};
</script>
