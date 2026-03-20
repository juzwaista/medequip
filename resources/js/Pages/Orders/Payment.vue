<template>
    <MainLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Pay Invoice</h1>
                    <p class="text-gray-600 mt-1">{{ invoice.invoice_number }}</p>
                </div>
                <Link :href="`/orders/${invoice.order.id}`" class="text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Order
                </Link>
            </div>

            <!-- Flash error -->
            <div v-if="flash?.error" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">
                {{ flash.error }}
            </div>

            <!-- Already paid -->
            <div v-if="invoice.status === 'paid'" class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                <svg class="h-16 w-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-green-800">Invoice Fully Paid</h2>
                <p class="text-green-600 mt-2">Thank you! Your payment has been received.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <!-- Payment Options -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- PayMongo Option -->
                    <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-blue-100">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                </svg>
                                Pay Online
                            </h2>
                            <p class="text-blue-100 text-sm mt-1">Secure checkout via PayMongo</p>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600 text-sm mb-4">You'll be redirected to PayMongo's secure checkout page.</p>
                            <!-- Accepted methods -->
                            <div class="flex gap-3 mb-6">
                                <span class="flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full">💳 Card</span>
                                <span class="flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full">📱 GCash</span>
                                <span class="flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full">🔵 Maya</span>
                                <span class="flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full">🟢 GrabPay</span>
                            </div>
                            <form @submit.prevent="payOnline">
                                <button
                                    type="submit"
                                    :disabled="payingOnline"
                                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="payingOnline" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ payingOnline ? 'Redirecting...' : `Pay ₱${Number(invoice.total_amount).toLocaleString()}` }}
                                </button>
                            </form>
                            <p class="text-xs text-gray-400 text-center mt-3">🔒 Secured by PayMongo</p>
                        </div>
                    </div>

                    <!-- Manual Payment Option -->
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                        <button
                            type="button"
                            @click="showManual = !showManual"
                            class="w-full px-6 py-4 flex justify-between items-center text-left"
                        >
                            <span class="font-semibold text-gray-800">Pay via Cash / Bank Transfer</span>
                            <svg :class="{ 'rotate-180': showManual }" class="h-5 w-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div v-show="showManual" class="px-6 pb-6 border-t border-gray-100 pt-4">
                            <p class="text-sm text-gray-600 mb-4">Submit your payment details. The distributor will verify your payment.</p>
                            <form @submit.prevent="payManual" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Payment Method *</label>
                                    <select v-model="manualForm.payment_method" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                                        <option value="">Select method</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="gcash">GCash</option>
                                        <option value="paymaya">Maya</option>
                                    </select>
                                    <p v-if="manualForm.errors.payment_method" class="text-red-500 text-xs mt-1">{{ manualForm.errors.payment_method }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Amount Paid *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                        <input v-model="manualForm.amount" type="number" step="0.01" :max="invoice.total_amount" required
                                            class="w-full pl-8 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"/>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Reference Number</label>
                                    <input v-model="manualForm.reference_number" type="text" placeholder="e.g. transaction ID"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Proof of Payment</label>
                                    <input type="file" @change="e => manualForm.proof = e.target.files[0]" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700"/>
                                    <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG (max 5MB)</p>
                                </div>
                                <button type="submit" :disabled="manualForm.processing"
                                    class="w-full bg-gray-800 text-white py-3 rounded-xl font-semibold hover:bg-gray-900 transition disabled:opacity-50">
                                    {{ manualForm.processing ? 'Submitting...' : 'Submit Payment' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Invoice Summary Sidebar -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-md p-6 sticky top-8">
                        <h3 class="font-bold text-gray-900 text-lg mb-4">Invoice Summary</h3>

                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Invoice #</span>
                                <span class="font-mono font-semibold text-gray-900">{{ invoice.invoice_number }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Due Date</span>
                                <span class="font-medium" :class="isOverdue ? 'text-red-600 font-bold' : 'text-gray-900'">
                                    {{ new Date(invoice.due_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                    <span v-if="isOverdue" class="text-xs bg-red-100 text-red-700 px-1.5 rounded ml-1">OVERDUE</span>
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 space-y-2 mb-4">
                            <div v-for="item in invoice.order.items" :key="item.id" class="flex justify-between text-sm">
                                <span class="text-gray-700">{{ item.product.name }} <span class="text-gray-400">×{{ item.quantity }}</span></span>
                                <span class="font-medium text-gray-900">₱{{ Number(item.total_price).toLocaleString() }}</span>
                            </div>
                        </div>

                        <div class="border-t-2 border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900">Total Due</span>
                                <span class="text-2xl font-black text-blue-600">₱{{ Number(invoice.total_amount).toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    invoice: Object,
    flash: Object,
});

const showManual  = ref(false);
const payingOnline = ref(false);

const isOverdue = computed(() =>
    props.invoice.status !== 'paid' && new Date(props.invoice.due_date) < new Date()
);

const manualForm = useForm({
    payment_method:   '',
    amount:           props.invoice.total_amount,
    reference_number: '',
    proof:            null,
});

const payOnline = () => {
    payingOnline.value = true;
    router.post(`/invoices/${props.invoice.id}/checkout`, {}, {
        onError: () => { payingOnline.value = false; },
    });
};

const payManual = () => {
    manualForm.post(`/invoices/${props.invoice.id}/pay/manual`, {
        forceFormData: true,
    });
};
</script>
