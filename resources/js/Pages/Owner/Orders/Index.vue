<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
                    <p class="text-gray-600 mt-2">Manage customer orders</p>
                </div>
                <a href="/owner/dashboard" class="text-blue-600 hover:text-blue-700 font-medium">
                    ← Back to Dashboard
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                        <input 
                            v-model="localFilters.search"
                            @keyup.enter="applyFilters"
                            type="text" 
                            placeholder="Order number or customer name..." 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select 
                            v-model="localFilters.status"
                            @change="applyFilters"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="packed">Packed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            <div v-if="!orders || !orders.data" class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                <p class="text-yellow-800">
                    <strong>Debug:</strong> Orders data is missing or invalid. 
                    <span v-if="orders">Total: {{ orders.total || 'unknown' }}</span>
                </p>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <table v-if="orders && orders.data && orders.data.length > 0" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bgwhite divide-y divide-gray-200">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900">{{ order.order_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ order.customer?.name || 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ order.customer?.email || '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ order.items?.length || 0 }} item(s)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">₱{{ Number(order.total_amount).toLocaleString() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusBadge :status="order.status" type="order" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <DateFormat :date="order.created_at" format="short" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a 
                                    :href="`/owner/orders/${order.id}`"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    View Details
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-else class="p-12 text-center text-gray-500">
                    <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p>No orders found</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders.data.length > 0" class="mt-6 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-for="link in orders.links" 
                        :key="link.label"
                        :href="link.url"
                        :class="{
                            'bg-blue-600 text-white': link.active,
                            'bg-white text-gray-700 hover:bg-gray-50': !link.active,
                            'opacity-50 cursor-not-allowed': !link.url,
                        }"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium"
                        v-html="link.label"
                    >
                    </Link>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DateFormat from '@/Components/DateFormat.vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
        default: () => ({ data: [], total: 0, links: [] })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
});

const localFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const applyFilters = () => {
    router.get('/owner/orders', localFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
