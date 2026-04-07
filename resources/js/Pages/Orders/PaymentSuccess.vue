<template>
    <MainLayout>
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <!-- Polling spinner — shown while webhook processes -->
            <div v-if="!isPaid && polling" class="mb-8">
                <div class="w-20 h-20 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="animate-spin h-10 w-10 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Confirming Payment…</h1>
                <p class="text-gray-500">Please wait while we verify your payment with PayMongo.</p>
            </div>

            <!-- Paid confirmation -->
            <div v-else-if="isPaid || localPaid" class="mb-8">
                <div class="w-24 h-24 mx-auto bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mb-6 shadow-lg">
                    <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-gray-900 mb-3">Payment Received</h1>
                <p class="text-gray-600 mb-2">Thank you for your payment.</p>
                <p class="text-sm font-mono text-gray-500 bg-gray-100 rounded-lg px-4 py-2 inline-block">
                    {{ invoice.invoice_number }}
                </p>
            </div>

            <!-- Fallback — payment not yet confirmed -->
            <div v-else class="mb-8">
                <div class="w-24 h-24 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="h-12 w-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-3">Payment Processing</h1>
                <p class="text-gray-600">Your payment is being processed. You'll receive a confirmation shortly.</p>
            </div>

            <!-- Invoice detail card -->
            <div class="bg-white rounded-2xl shadow-md p-6 text-left mb-8">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-semibold text-gray-600">Invoice</span>
                    <span class="font-mono text-gray-900 font-bold">{{ invoice.invoice_number }}</span>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-semibold text-gray-600">Amount</span>
                    <span class="text-xl font-black text-blue-600">₱{{ Number(invoice.total_amount).toLocaleString() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-600">Status</span>
                    <span :class="{
                        'bg-green-100 text-green-800': invoice.status === 'paid',
                        'bg-yellow-100 text-yellow-800': invoice.status === 'unpaid',
                        'bg-blue-100 text-blue-800': invoice.status === 'partial',
                    }" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
                        {{ invoice.status }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Link :href="`/orders/${invoice.order.id}`"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transition shadow-lg">
                    View Order Details
                </Link>
                <Link href="/my-orders"
                    class="border-2 border-gray-300 text-gray-700 px-8 py-3 rounded-xl font-bold hover:bg-gray-50 transition">
                    My Orders
                </Link>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    invoice: Object,
    order:   Object,
    isPaid:  Boolean,
});

const localPaid = ref(props.isPaid);
const polling   = ref(!props.isPaid);
let   pollTimer = null;

// Poll the page for up to 30 seconds waiting for webhook to confirm
onMounted(() => {
    if (props.isPaid) return;

    let attempts = 0;
    pollTimer = setInterval(() => {
        attempts++;
        router.reload({ only: ['invoice', 'isPaid'], onSuccess: (page) => {
            if (page.props.isPaid) {
                localPaid.value = true;
                polling.value   = false;
                clearInterval(pollTimer);
            }
        }});
        if (attempts >= 6) { // 6 × 5s = 30s timeout
            polling.value = false;
            clearInterval(pollTimer);
        }
    }, 5000);
});

onUnmounted(() => { if (pollTimer) clearInterval(pollTimer); });
</script>
