<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-6 pb-28 sm:pb-10 min-w-0">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold text-ink">Orders</h1>
                    <p class="text-ink-soft mt-1 text-sm sm:text-base">Manage customer orders</p>
                </div>
                <a href="/owner/dashboard" class="text-brand hover:text-brand-dark font-medium text-sm sm:text-base py-2 sm:py-0 inline-flex items-center min-h-[44px] sm:min-h-0 touch-manipulation shrink-0">
                    ← Dashboard
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-card shadow-md p-4 sm:p-6 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-2">Search</label>
                        <input 
                            v-model="localFilters.search"
                            @keyup.enter="applyFilters"
                            type="text" 
                            placeholder="Order number or customer name..." 
                            class="w-full px-4 py-3 sm:py-2 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent min-h-[48px] text-base sm:text-sm touch-manipulation"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-2">Status</label>
                        <select 
                            v-model="localFilters.status"
                            @change="applyFilters"
                            class="w-full px-4 py-3 sm:py-2 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent min-h-[48px] text-base sm:text-sm touch-manipulation"
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

            <!-- Orders: cards on small screens, table on md+ -->
            <div class="bg-white rounded-card shadow-md overflow-hidden">
                <!-- Mobile / narrow -->
                <div v-if="orders && orders.data && orders.data.length > 0" class="md:hidden divide-y divide-line">
                    <Link
                        v-for="order in orders.data"
                        :key="'m-' + order.id"
                        :href="`/owner/orders/${order.order_number}`"
                        class="block p-4 hover:bg-mist active:bg-mist transition min-h-[72px] touch-manipulation"
                    >
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-ink truncate">{{ order.order_number }}</p>
                                <p class="text-sm text-ink-soft truncate">{{ order.customer?.name || 'Unknown' }}</p>
                                <p class="text-xs text-ink-soft mt-1">{{ order.items?.length || 0 }} item(s)</p>
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                <p class="text-sm font-bold text-ink">₱{{ Number(order.total_amount).toLocaleString() }}</p>
                                <div class="mt-1">
                                    <StatusBadge :status="order.status" type="order" />
                                </div>
                                <p class="text-xs text-ink-soft mt-1"><DateFormat :date="order.created_at" format="short" /></p>
                            </div>
                        </div>
                        <p class="text-xs text-brand font-semibold mt-2">View details →</p>
                    </Link>
                </div>

                <table v-if="orders && orders.data && orders.data.length > 0" class="hidden md:table min-w-full divide-y divide-line">
                    <thead class="bg-mist">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-soft  ">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-soft  ">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-soft  ">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-soft  ">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-soft  ">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-soft  ">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-soft  ">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bgwhite divide-y divide-line">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-mist">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-ink">{{ order.order_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-ink">{{ order.customer?.name || 'Unknown' }}</div>
                                <div class="text-xs text-ink-soft">{{ order.customer?.email || '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-ink-soft">
                                {{ order.items?.length || 0 }} item(s)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-ink">₱{{ Number(order.total_amount).toLocaleString() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusBadge :status="order.status" type="order" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-ink-soft">
                                <DateFormat :date="order.created_at" format="short" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a 
                                    :href="`/owner/orders/${order.order_number}`"
                                    class="text-brand hover:text-brand-dark"
                                >
                                    View Details
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-else class="p-12 text-center text-ink-soft">
                    <svg class="h-16 w-16 mx-auto mb-4 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p>No orders found</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders?.data?.length > 0" class="mt-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div class="text-sm text-ink-soft text-center sm:text-left">
                    Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                </div>
                <div class="flex flex-wrap justify-center sm:justify-end gap-2">
                    <template v-for="link in orders.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{
                                'bg-brand text-white border-brand': link.active,
                                'bg-white text-ink hover:bg-mist border-line': !link.active,
                            }"
                            class="px-3 py-2.5 sm:py-2 border rounded-control text-sm font-medium min-w-[40px] text-center touch-manipulation inline-flex items-center justify-center"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2.5 sm:py-2 border border-line rounded-control text-sm font-medium min-w-[40px] text-center opacity-50 cursor-not-allowed bg-mist text-ink-faint inline-flex items-center justify-center"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
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
