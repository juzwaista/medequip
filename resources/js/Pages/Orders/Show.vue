<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-gray-600 mt-2">{{ order.order_number }}</p>
                </div>
                <Link href="/my-orders" class="text-blue-600 hover:text-blue-700 font-medium flex items-center">
                    <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to My Orders
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Timeline -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <OrderTimeline 
                            :current-status="order.status"
                            :created-at="order.created_at"
                            :approved-at="order.approved_at"
                            :packed-at="order.packed_at"
                            :shipped-at="order.shipped_at"
                            :delivered-at="order.delivered_at"
                            :cancelled-at="order.cancelled_at"
                            :rejected-at="order.rejected_at"
                        />
                    </div>

                    <!-- Order Info -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Order Information</h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Placed on <DateFormat :date="order.created_at" format="datetime" />
                                </p>
                            </div>
                            <StatusBadge :status="order.status" type="order" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Distributor</p>
                                <Link 
                                    v-if="order.distributor.slug"
                                    :href="`/seller/${order.distributor.slug}`"
                                    class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                                >
                                    {{ order.distributor.company_name }}
                                </Link>
                                <p v-else class="font-semibold text-gray-900">{{ order.distributor.company_name }}</p>
                                <p class="text-sm text-gray-600">{{ order.distributor.email }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Delivery Address</p>
                                <p class="text-gray-900">{{ order.delivery_address }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Contact Number</p>
                                <p class="font-semibold text-gray-900">{{ order.contact_number }}</p>
                            </div>
                            <div v-if="order.notes">
                                <p class="text-sm font-medium text-gray-600 mb-1">Notes</p>
                                <p class="text-sm text-gray-700">{{ order.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items with Images -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Order Items</h2>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="item in order.items" 
                                :key="item.id"
                                class="flex gap-4 pb-4 border-b last:border-0 hover:bg-gray-50 rounded-lg p-3 -m-3 transition"
                            >
                                <!-- Product Image -->
                                <div class="w-24 h-24 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden">
                                    <img 
                                        v-if="item.product.image_path"
                                        :src="`/storage/${item.product.image_path}`" 
                                        :alt="item.product.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ item.product.name }}</h3>
                                    <p v-if="item.product.sku" class="text-sm text-gray-600 mt-1">SKU: {{ item.product.sku }}</p>
                                    
                                    <div class="flex flex-wrap items-center gap-3 mt-3">
                                        <span class="text-sm text-gray-600">Quantity: <span class="font-semibold">{{ item.quantity }}</span></span>
                                        <span class="text-gray-300">•</span>
                                        <div class="flex items-center gap-1">
                                            <PriceDisplay :amount="item.unit_price" size="small" />
                                            <span class="text-sm text-gray-600">each</span>
                                        </div>
                                        <span 
                                            v-if="item.is_wholesale" 
                                            class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-700 text-xs px-3 py-1 rounded-full font-semibold"
                                        >
                                            Wholesale Price
                                        </span>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="text-right self-center">
                                    <p class="text-xs text-gray-600 mb-1">Subtotal</p>
                                    <PriceDisplay :amount="item.total_price" />
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="mt-6 pt-6 border-t-2 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-gray-900">Total Amount</span>
                                <PriceDisplay :amount="order.total_amount" size="large" color="blue" />
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Tracking -->
                    <div v-if="order.delivery" class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Delivery Tracking</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Tracking Number</p>
                                <p class="font-mono font-semibold text-gray-900 text-lg">{{ order.delivery.tracking_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Carrier</p>
                                <p class="font-semibold text-gray-900">{{ order.delivery.carrier }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Delivery Status</p>
                                <StatusBadge :status="order.delivery.status" type="delivery" />
                            </div>
                            <div v-if="order.delivery.actual_delivery_at">
                                <p class="text-sm font-medium text-gray-600 mb-1">Delivered On</p>
                                <p class="font-semibold text-gray-900">
                                    <DateFormat :date="order.delivery.actual_delivery_at" format="datetime" />
                                </p>
                            </div>
                            
                            <!-- Proof of Delivery Image -->
                            <div v-if="order.delivery.proof_of_delivery_path" class="md:col-span-2 pt-4 border-t mt-2">
                                <p class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-3">Proof of Delivery</p>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-2 inline-block">
                                    <img 
                                        :src="`/storage/${order.delivery.proof_of_delivery_path}`" 
                                        alt="Proof of Delivery Photo" 
                                        class="max-w-xs md:max-w-md rounded-lg shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Invoice -->
                    <div v-if="order.invoice" class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-md p-6 border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Invoice
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Invoice Number</p>
                                <p class="font-mono text-sm font-semibold text-gray-900">{{ order.invoice.invoice_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Status</p>
                                <StatusBadge :status="order.invoice.status" type="invoice" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Due Date</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    <DateFormat :date="order.invoice.due_date" format="short" />
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 text-white">
                        <h3 class="font-bold mb-4 text-lg">Quick Actions</h3>
                        <div class="space-y-3">
                            <button 
                                v-if="order.status === 'pending' || order.status === 'approved'"
                                @click="cancelOrder"
                                class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm transition px-4 py-3 rounded-lg font-medium text-left flex items-center"
                            >
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel Order
                            </button>
                            <button class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm transition px-4 py-3 rounded-lg font-medium text-left flex items-center">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Contact Distributor
                            </button>
                            <Link 
                                href="/products"
                                class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm transition px-4 py-3 rounded-lg font-medium text-left flex items-center"
                            >
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Continue Shopping
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import OrderTimeline from '@/Components/OrderTimeline.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DateFormat from '@/Components/DateFormat.vue';
import PriceDisplay from '@/Components/PriceDisplay.vue';

const props = defineProps({
    order: Object,
});

const cancelOrder = () => {
    if (confirm(`Are you sure you want to cancel order ${props.order.order_number}? This action cannot be undone.`)) {
        console.log('[OrderShow] Cancelling order', props.order.id);
        
        router.post(`/orders/${props.order.id}/cancel`, {}, {
            onSuccess: () => {
                console.log('[OrderShow] Order cancelled successfully');
            },
            onError: (errors) => {
                console.error('[OrderShow] Cancel failed', errors);
            }
        });
    }
};
</script>
