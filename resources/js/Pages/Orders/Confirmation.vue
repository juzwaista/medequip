<template>
    <MainLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mx-auto mb-6">
                    <svg class="h-12 w-12 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
                <p class="text-gray-600">Your checkout created {{ shopsCount }} order<span v-if="shopsCount > 1">s</span> from {{ shopsCount }} shop<span v-if="shopsCount > 1">s</span>.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Billing Summary</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Shops</p>
                        <p class="font-bold text-gray-900">{{ shopsCount }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Items</p>
                        <p class="font-bold text-gray-900">{{ itemsCount }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Shipping Total</p>
                        <p class="font-bold text-gray-900">₱{{ Number(shippingTotal).toLocaleString() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Grand Total</p>
                        <p class="font-bold text-blue-600">₱{{ Number(grandTotal).toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 mb-6">
                <div
                    v-for="entry in normalizedOrders"
                    :key="entry.id"
                    class="bg-white rounded-xl shadow-md p-6"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                        <div>
                            <p class="text-xs text-gray-500">Order Number</p>
                            <p class="font-bold text-blue-600">{{ entry.order_number }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ entry.distributor?.company_name || 'Distributor' }}</p>
                        </div>
                        <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold capitalize">
                            {{ entry.status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-4 pb-4 border-b">
                        <div>
                            <p class="text-gray-500">Subtotal</p>
                            <p class="font-semibold text-gray-900">₱{{ Number(entry.subtotal || 0).toLocaleString() }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Shipping</p>
                            <p class="font-semibold text-gray-900">₱{{ Number(entry.shipping_fee || 0).toLocaleString() }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Order Total</p>
                            <p class="font-semibold text-gray-900">₱{{ Number(entry.total_amount || 0).toLocaleString() }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Payment</p>
                            <p class="font-semibold text-gray-900">{{ formatPaymentMethod(entry.payment_method) }}</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div
                            v-for="item in (entry.items || [])"
                            :key="item.id"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-gray-700">{{ item.product?.name }} ({{ item.quantity }}x)</span>
                            <span class="font-semibold text-gray-900">₱{{ Number(item.total_price || 0).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6 mb-6">
                <p class="text-sm text-gray-700">
                    Your total payment of <strong>₱{{ Number(grandTotal).toLocaleString() }}</strong> is secured by escrow.
                    Funds are released per order after delivery confirmation.
                </p>
            </div>

            <div class="flex gap-4">
                <Link href="/my-orders" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center px-6 py-3 rounded-xl hover:shadow-xl transition font-bold">
                    View My Orders
                </Link>
                <Link href="/products" class="flex-1 border-2 border-gray-300 text-gray-700 text-center px-6 py-3 rounded-xl hover:bg-gray-50 transition font-bold">
                    Continue Shopping
                </Link>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    order: Object,
    orders: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const normalizedOrders = computed(() => {
    if (props.orders?.length) return props.orders;
    return props.order ? [props.order] : [];
});

const shopsCount = computed(() => Number(props.summary?.shops_count ?? normalizedOrders.value.length ?? 1));
const itemsCount = computed(() => Number(props.summary?.items_count ?? normalizedOrders.value.reduce((sum, o) => sum + (o.items?.length || 0), 0)));
const shippingTotal = computed(() => Number(props.summary?.shipping_total ?? normalizedOrders.value.reduce((sum, o) => sum + Number(o.shipping_fee || 0), 0)));
const grandTotal = computed(() => Number(props.summary?.grand_total ?? normalizedOrders.value.reduce((sum, o) => sum + Number(o.total_amount || 0), 0)));

const formatPaymentMethod = (method) => {
    const labels = {
        'gcash': 'GCash',
        'paymaya': 'Maya',
        'card': 'Credit/Debit Card',
        'grab_pay': 'GrabPay',
        'bank_transfer': 'Bank Transfer',
        'paymongo': 'Online Payment',
        'wallet': 'Wallet',
    };
    return labels[method] || method;
};
</script>
