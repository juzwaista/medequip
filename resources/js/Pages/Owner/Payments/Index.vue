<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-ink">Payment Management</h1>
                <p class="text-ink-soft mt-2">Review and verify customer payments</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-amber-50 border border-amber-200 rounded-card p-5 flex items-center gap-4">
                    <div class="bg-amber-100 rounded-control p-3">
                        <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-amber-900">{{ stats.pending }}</p>
                        <p class="text-sm text-amber-700">Pending</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-5 flex items-center gap-4">
                    <div class="bg-brand-tint rounded-control p-3">
                        <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-brand-dark">₱{{ Number(stats.total_net || 0).toLocaleString() }}</p>
                        <p class="text-sm text-brand-dark">Net Revenue</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-5 flex items-center gap-4">
                    <div class="bg-brand-tint rounded-control p-3">
                        <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-brand-dark">₱{{ Number(stats.escrow_held || 0).toLocaleString() }}</p>
                        <p class="text-sm text-brand-dark">Held by platform</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-5 flex items-center gap-4">
                    <div class="bg-brand-tint rounded-control p-3">
                        <svg class="h-6 w-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-brand-dark">₱{{ Number(stats.escrow_released || 0).toLocaleString() }}</p>
                        <p class="text-sm text-brand-dark">Released</p>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-card shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-mist border-b border-line">
                                <th class="text-left px-6 py-4 text-xs font-bold text-ink-soft  ">Customer / Invoice</th>
                                <th class="text-left px-6 py-4 text-xs font-bold text-ink-soft  ">Method</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-ink-soft  ">Gross</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-ink-soft  ">Fee</th>
                                <th class="text-right px-6 py-4 text-xs font-bold text-ink-soft  ">Net Payout</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-ink-soft  ">Status</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-ink-soft  ">Payout</th>
                                <th class="text-center px-6 py-4 text-xs font-bold text-ink-soft  ">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-if="payments.data.length === 0">
                                <td colspan="8" class="text-center py-16 text-ink-faint">
                                    <svg class="h-12 w-12 mx-auto mb-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    No payments found
                                </td>
                            </tr>
                            <tr v-for="payment in payments.data" :key="payment.id"
                                class="hover:bg-mist transition">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-ink text-sm">{{ payment.invoice?.order?.customer?.name }}</p>
                                    <p class="font-mono text-xs text-ink-soft">{{ payment.invoice?.invoice_number }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 bg-mist text-ink px-3 py-1 rounded-full text-xs font-semibold capitalize">
                                        {{ formatMethod(payment.payment_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-ink-soft">
                                    ₱{{ Number(payment.amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-red-500">
                                    -₱{{ Number(payment.platform_fee_amount || 0).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-ink">
                                    ₱{{ Number(payment.net_seller_amount || 0).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-amber-100 text-amber-800': payment.status === 'pending',
                                        'bg-brand-tint text-brand-dark': payment.status === 'verified',
                                        'bg-red-100 text-red-800':    payment.status === 'rejected',
                                    }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-brand-tint text-brand-dark':    payment.escrow_status === 'held',
                                        'bg-brand-tint text-brand-dark':  payment.escrow_status === 'released',
                                        'bg-red-100 text-red-800':      payment.escrow_status === 'refunded',
                                    }" class="px-3 py-1 rounded-full text-xs font-bold">
                                        {{ formatHoldStatus(payment.escrow_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="payment.status === 'pending' && payment.payment_method === 'bank_transfer'" class="flex gap-2 justify-center">
                                        <button
                                            @click="verifyPayment(payment)"
                                            class="bg-brand hover:bg-brand-dark text-white text-xs font-bold px-3 py-1.5 rounded-control transition"
                                        >
                                            Verify
                                        </button>
                                        <button
                                            @click="rejectPayment(payment)"
                                            class="bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold px-3 py-1.5 rounded-control transition"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <a v-if="payment.proof_of_payment_path"
                                        :href="`/storage/${payment.proof_of_payment_path}`"
                                        target="_blank"
                                        class="text-brand hover:text-brand-dark text-xs font-medium underline block text-center mt-1">
                                        View Proof
                                    </a>
                                    <span v-if="payment.payment_method === 'paymongo' && payment.status === 'verified'"
                                        class="text-xs text-ink-faint block text-center">
                                        Auto-verified
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.last_page > 1" class="px-6 py-4 border-t border-line flex justify-center gap-2">
                    <Link v-for="link in payments.links" :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded-control text-sm transition',
                            link.active ? 'bg-brand text-white font-bold' : 'text-ink-soft hover:bg-mist',
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

const formatHoldStatus = (status) => {
    const labels = {
        held: 'Held by platform',
        released: 'Paid out',
        refunded: 'Refunded',
    };
    return labels[status] || status || '—';
};

const verifyPayment = (payment) => {
    if (!confirm('Verify this bank transfer payment? Funds will be held by the platform until the buyer confirms delivery.')) return;
    router.post(`/owner/payments/${payment.id}/verify`, {}, { preserveScroll: true });
};

const rejectPayment = (payment) => {
    if (!confirm('Reject this payment?')) return;
    router.post(`/owner/payments/${payment.id}/reject`, {}, { preserveScroll: true });
};
</script>
