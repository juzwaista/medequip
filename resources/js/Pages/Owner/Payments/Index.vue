<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Payment Management</h1>
                <p class="text-gray-600 mt-2">Review and verify customer payments</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-center gap-4">
                    <div class="bg-amber-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-900">{{ stats.pending }}</p>
                        <p class="text-sm text-amber-700">Pending</p>
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-5 flex items-center gap-4">
                    <div class="bg-green-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-green-900">₱{{ Number(stats.total_net || 0).toLocaleString() }}</p>
                        <p class="text-sm text-green-700">Net Revenue</p>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 flex items-center gap-4">
                    <div class="bg-blue-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-blue-900">₱{{ Number(stats.escrow_held || 0).toLocaleString() }}</p>
                        <p class="text-sm text-blue-700">In Escrow</p>
                    </div>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 flex items-center gap-4">
                    <div class="bg-emerald-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-900">₱{{ Number(stats.escrow_released || 0).toLocaleString() }}</p>
                        <p class="text-sm text-emerald-700">Released</p>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Customer / Invoice</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Gross</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Fee</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Net Payout</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Escrow</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="payments.data.length === 0">
                                <td colspan="8" class="text-center py-16 text-gray-400">
                                    <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    No payments found
                                </td>
                            </tr>
                            <tr v-for="payment in payments.data" :key="payment.id"
                                class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900 text-sm">{{ payment.invoice?.order?.customer?.name }}</p>
                                    <p class="font-mono text-xs text-gray-500">{{ payment.invoice?.invoice_number }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-semibold capitalize">
                                        {{ formatMethod(payment.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-600">
                                    ₱{{ Number(payment.amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-red-500">
                                    -₱{{ Number(payment.platform_fee_amount || 0).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900">
                                    ₱{{ Number(payment.net_seller_amount || 0).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-amber-100 text-amber-800': payment.status === 'pending',
                                        'bg-green-100 text-green-800': payment.status === 'verified',
                                        'bg-red-100 text-red-800':    payment.status === 'rejected',
                                    }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-blue-100 text-blue-800':    payment.escrow_status === 'held',
                                        'bg-green-100 text-green-800':  payment.escrow_status === 'released',
                                        'bg-red-100 text-red-800':      payment.escrow_status === 'refunded',
                                    }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                        {{ payment.escrow_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="payment.status === 'pending' && payment.payment_method === 'bank_transfer'" class="flex gap-2 justify-center">
                                        <button
                                            @click="verifyPayment(payment)"
                                            class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition"
                                        >
                                            Verify
                                        </button>
                                        <button
                                            @click="rejectPayment(payment)"
                                            class="bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold px-3 py-1.5 rounded-lg transition"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <a v-if="payment.proof_of_payment_path"
                                        :href="`/storage/${payment.proof_of_payment_path}`"
                                        target="_blank"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium underline block text-center mt-1">
                                        View Proof
                                    </a>
                                    <span v-if="payment.payment_method === 'paymongo' && payment.status === 'verified'"
                                        class="text-xs text-gray-400 block text-center">
                                        Auto-verified
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2">
                    <Link v-for="link in payments.links" :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded-lg text-sm transition',
                            link.active ? 'bg-blue-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-100',
                            !link.url ? 'opacity-40 cursor-not-allowed' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    payments: Object,
    stats:    Object,
});

const formatMethod = (method) => {
    const labels = {
        'gcash': 'GCash', 'paymaya': 'Maya', 'card': 'Card',
        'grab_pay': 'GrabPay', 'bank_transfer': 'Bank', 'paymongo': 'Online',
    };
    return labels[method] || method;
};

const verifyPayment = (payment) => {
    if (!confirm('Verify this bank transfer payment? Funds will be held in escrow.')) return;
    router.post(`/owner/payments/${payment.id}/verify`, {}, { preserveScroll: true });
};

const rejectPayment = (payment) => {
    if (!confirm('Reject this payment?')) return;
    router.post(`/owner/payments/${payment.id}/reject`, {}, { preserveScroll: true });
};
</script>
