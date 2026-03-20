<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link href="/admin/couriers" class="text-gray-500 hover:text-gray-700">
                    &larr; Back to Couriers
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight border-l pl-4 border-gray-300">Platform Deliveries Oversight</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Filters -->
                <div class="bg-white p-4 shadow-sm sm:rounded-lg flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Filter by Status:</span>
                        <select v-model="statusFilter" @change="applyFilter" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Tracking # / Order</th>
                                    <th scope="col" class="px-6 py-4">Courier Assigned</th>
                                    <th scope="col" class="px-6 py-4">Customer</th>
                                    <th scope="col" class="px-6 py-4 border-l">Status</th>
                                    <th scope="col" class="px-6 py-4 text-right">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="delivery in deliveries.data" :key="delivery.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-gray-900">{{ delivery.tracking_number }}</div>
                                        <div class="text-xs text-gray-500 mt-1">Order #{{ (delivery.order_id || '000').toString().padStart(6, '0') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="delivery.courier" class="font-medium text-gray-900">
                                            {{ delivery.courier.user.name }}
                                        </div>
                                        <span v-else class="text-xs italic text-gray-400">Unassigned</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ delivery.order?.customer?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-[200px]" :title="delivery.order?.shipping_address">{{ delivery.order?.shipping_address }}</div>
                                    </td>
                                    <td class="px-6 py-4 border-l">
                                        <span 
                                            :class="{
                                                'bg-gray-100 text-gray-800': delivery.status === 'pending',
                                                'bg-yellow-100 text-yellow-800': delivery.status === 'picked_up',
                                                'bg-blue-100 text-blue-800': delivery.status === 'in_transit',
                                                'bg-green-100 text-green-800': delivery.status === 'delivered',
                                                'bg-red-100 text-red-800': delivery.status === 'failed',
                                            }"
                                            class="px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider"
                                        >
                                            {{ delivery.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-400 text-xs">
                                        {{ new Date(delivery.updated_at).toLocaleString() }}
                                    </td>
                                </tr>
                                <tr v-if="deliveries.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">No deliveries found matching the criteria.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="deliveries.links && deliveries.links.length > 3" class="flex justify-center mt-6">
                    <div class="flex shadow-sm rounded-md bg-white">
                        <template v-for="(link, i) in deliveries.links" :key="i">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 border text-sm font-medium',
                                    link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                    i === 0 ? 'rounded-l-md' : '',
                                    i === deliveries.links.length - 1 ? 'rounded-r-md' : ''
                                ]"
                                v-html="link.label"
                            />
                            <span 
                                v-else
                                :class="[
                                    'px-4 py-2 border text-sm font-medium bg-gray-50 border-gray-300 text-gray-400 cursor-not-allowed',
                                    i === 0 ? 'rounded-l-md' : '',
                                    i === deliveries.links.length - 1 ? 'rounded-r-md' : ''
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
