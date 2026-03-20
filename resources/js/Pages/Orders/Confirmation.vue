<template>
    <MainLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <!-- Success Icon -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mx-auto mb-6">
                    <svg class="h-12 w-12 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
                <p class="text-gray-600">Thank you for your order. We'll process it shortly.</p>
            </div>

            <!-- Order Details -->
            <div class="bg-white rounded-xl shadow-md p-8 mb-6">
                <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Order Number</p>
                        <p class="font-bold text-lg text-blue-600">{{ order.order_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Status</p>
                        <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold capitalize">
                            {{ order.status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Amount</p>
                        <p class="font-bold text-lg text-gray-900">₱{{ Number(order.total_amount).toLocaleString() }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Payment Method</p>
                        <p class="font-semibold text-gray-900 capitalize">{{ formatPaymentMethod(order.payment_method) }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Order Items</h3>
                    <div class="space-y-2">
                        <div 
                            v-for="item in order.items" 
                            :key="item.id"
                            class="flex justify-between text-sm py-2 border-b last:border-0"
                        >
                            <span class="text-gray-700">{{ item.product.name }} ({{ item.quantity }}x)</span>
                            <span class="font-semibold">₱{{ Number(item.total_price).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Delivery Address</h3>
                    <p class="text-gray-700">{{ order.delivery_address }}</p>
                    <p class="text-gray-600 text-sm mt-1">Contact: {{ order.contact_number }}</p>
                </div>
            </div>

            <!-- Payment & Escrow Status -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6 mb-6">
                <div class="flex items-start gap-3">
                    <div class="bg-green-100 rounded-lg p-2 flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">Payment Secured</h3>
                        <p class="text-sm text-gray-700">
                            Your payment of <strong>₱{{ Number(order.total_amount).toLocaleString() }}</strong> is securely held by MedEquip.
                            Funds will be released to the seller only after you confirm receipt of your order.
                        </p>
                        <div v-if="paymentStatus === 'pending'" class="mt-3 flex items-center gap-2 text-amber-700">
                            <svg class="h-4 w-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium">Awaiting payment verification</span>
                        </div>
                        <div v-else-if="paymentStatus === 'verified'" class="mt-3 flex items-center gap-2 text-green-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium">Payment verified — held in escrow</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <Link 
                    href="/my-orders"
                    class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center px-6 py-3 rounded-xl hover:shadow-xl transition font-bold"
                >
                    View My Orders
                </Link>
                <Link 
                    href="/products"
                    class="flex-1 border-2 border-gray-300 text-gray-700 text-center px-6 py-3 rounded-xl hover:bg-gray-50 transition font-bold"
                >
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
});

const paymentStatus = computed(() => {
    if (!props.order.invoice?.payments?.length) return 'pending';
    const latestPayment = props.order.invoice.payments[props.order.invoice.payments.length - 1];
    return latestPayment.status;
});

const formatPaymentMethod = (method) => {
    const labels = {
        'gcash': 'GCash',
        'paymaya': 'Maya',
        'card': 'Credit/Debit Card',
        'grab_pay': 'GrabPay',
        'bank_transfer': 'Bank Transfer',
        'paymongo': 'Online Payment',
    };
    return labels[method] || method;
};
</script>
