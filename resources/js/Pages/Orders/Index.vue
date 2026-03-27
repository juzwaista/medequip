<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
                <p class="text-gray-600 mt-2">Track and manage your orders</p>
            </div>



            <!-- Filters & Search -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input 
                            v-model="localFilters.search"
                            @input="debouncedSearch"
                            type="text"
                            placeholder="Order number or product name..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select 
                            v-model="localFilters.status"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="packed">Packed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <div class="flex gap-2">
                            <input 
                                v-model="localFilters.date_from"
                                @change="applyFilters"
                                type="date"
                                class="flex-1 px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                            />
                            <span class="self-center text-gray-500">to</span>
                            <input 
                                v-model="localFilters.date_to"
                                @change="applyFilters"
                                type="date"
                                class="flex-1 px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Clear Filters -->
                <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
                    <button 
                        @click="clearFilters"
                        class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                    >
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Orders List -->
            <div class="space-y-4">
                <div 
                    v-for="order in orders.data" 
                    :key="order.id"
                    class="bg-white rounded-xl shadow-md hover:shadow-lg transition overflow-hidden"
                >
                    <div class="p-6">
                        <!-- Order Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ order.order_number }}</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <DateFormat :date="order.created_at" format="long" />
                                </p>
                            </div>
                            <StatusBadge :status="order.status" type="order" />
                        </div>

                        <!-- Product Preview Images -->
                        <div v-if="order.preview_images && order.preview_images.length > 0" class="mb-4">
                            <div class="flex gap-2 items-center">
                                <div 
                                    v-for="(imagePath, idx) in order.preview_images" 
                                    :key="idx"
                                    class="w-16 h-16 rounded-lg overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200 flex-shrink-0"
                                >
                                    <img 
                                        v-if="imagePath"
                                        :src="`/storage/${imagePath}`" 
                                        :alt="`Product ${idx + 1}`"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                </div>
                                <span v-if="order.remaining_items > 0" class="text-sm text-gray-600 font-medium">
                                    +{{ order.remaining_items }} more {{ order.remaining_items === 1 ? 'item' : 'items' }}
                                </span>
                            </div>
                        </div>

                        <!-- Order Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 pb-4 border-b border-gray-100">
                            <div>
                                <p class="text-sm text-gray-600">Distributor</p>
                                <Link 
                                    v-if="order.distributor.slug"
                                    :href="`/seller/${order.distributor.slug}`"
                                    class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                                >
                                    {{ order.distributor.company_name }}
                                </Link>
                                <p v-else class="font-semibold text-gray-900">{{ order.distributor.company_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total Amount</p>
                                <PriceDisplay :amount="order.total_amount" color="blue" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Items</p>
                                <p class="text-sm font-semibold text-gray-900">{{ order.items.length }} item(s)</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3">
                            <Link 
                                :href="`/orders/${order.id}`"
                                class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm"
                            >
                                View Details
                            </Link>
                            <button 
                                v-if="order.status === 'delivered' && !order.received_at"
                                @click="confirmReceived(order)"
                                class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 transition font-medium text-sm flex items-center gap-1.5"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Confirm Received
                            </button>
                            <button 
                                v-if="order.status === 'shipped'"
                                class="px-5 py-2.5 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition font-medium text-sm"
                            >
                                Track Order
                            </button>
                            <button 
                                v-if="order.status === 'pending' || order.status === 'approved'"
                                @click="confirmCancel(order)"
                                class="px-5 py-2.5 border-2 border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition font-medium text-sm"
                            >
                                Cancel Order
                            </button>
                            <button
                                v-if="canPayNow(order)"
                                @click="payNow(order)"
                                class="px-5 py-2.5 border-2 border-emerald-600 text-emerald-700 rounded-lg hover:bg-emerald-50 transition font-medium text-sm"
                            >
                                Pay Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="orders.data.length === 0" class="bg-white rounded-xl shadow-md p-12 text-center">
                    <svg class="h-20 w-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">It's quiet here.</h3>
                    <p class="text-gray-600 mb-6">
                        {{ hasActiveFilters ? 'Try adjusting your filters' : "Start exploring to find the supplies you need." }}
                    </p>
                    <Link 
                        v-if="!hasActiveFilters"
                        href="/products" 
                        class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                    >
                        Find Equipment
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders.data.length > 0" class="mt-6 flex justify-center gap-2">
                <template v-for="link in orders.links" :key="link.label">
                    <Link 
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'px-4 py-2 rounded-lg',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-sm'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        :class="[
                            'px-4 py-2 rounded-lg bg-white text-gray-400 opacity-50 cursor-not-allowed shadow-sm'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DateFormat from '@/Components/DateFormat.vue';
import PriceDisplay from '@/Components/PriceDisplay.vue';

const props = defineProps({
    orders: Object,
    filters: Object,
});

const localFilters = reactive({
    status: props.filters.status || '',
    search: props.filters.search || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

let searchTimeout = null;

onMounted(() => {
    console.log('[OrdersIndex] Component mounted', {
        orders_count: props.orders.data.length,
        total: props.orders.total
    });
});

const hasActiveFilters = computed(() => {
    return localFilters.status !== '' || 
           localFilters.search !== '' || 
           localFilters.date_from !== '' || 
           localFilters.date_to !== '';
});

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
};

const applyFilters = () => {
    console.log('[OrdersIndex] Applying filters', localFilters);
    
    router.get('/my-orders', localFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    console.log('[OrdersIndex] Clearing filters');
    
    localFilters.status = '';
    localFilters.search = '';
    localFilters.date_from = '';
    localFilters.date_to = '';
    
    applyFilters();
};

const confirmCancel = (order) => {
    if (confirm(`Are you sure you want to cancel order ${order.order_number}? This action cannot be undone.`)) {
        console.log('[OrdersIndex] Cancelling order', order.id);
        
        router.post(`/orders/${order.id}/cancel`, {}, {
            onSuccess: () => {
                console.log('[OrdersIndex] Order cancelled successfully');
            },
            onError: (errors) => {
                console.error('[OrdersIndex] Cancel failed', errors);
            }
        });
    }
};

const confirmReceived = (order) => {
    if (confirm(`Confirm that you received order ${order.order_number}? This will release the payment to the seller.`)) {
        router.post(`/orders/${order.id}/confirm-received`, {}, {
            onSuccess: () => {
                console.log('[OrdersIndex] Order receipt confirmed');
            },
            onError: (errors) => {
                console.error('[OrdersIndex] Confirm received failed', errors);
            }
        });
    }
};

const canPayNow = (order) => {
    return !!order.can_pay_now;
};

const payNow = (order) => {
    router.post(`/orders/${order.id}/pay-now`);
};
</script>
