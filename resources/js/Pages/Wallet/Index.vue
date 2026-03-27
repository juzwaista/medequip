<template>
    <component :is="layoutComponent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Wallet</h1>
                    <p class="text-gray-600 mt-1">Manage your funds and transaction history</p>
                    <p v-if="page.props.auth?.user?.role === 'distributor'" class="text-sm text-gray-500 mt-2 max-w-xl">
                        Shop sales credit here when payments are verified (net after fees). Older entries may show as “Escrow payout” if they were released on delivery before wallet-on-verify.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Balance and Top-up -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Balance Card (Glassmorphism + Gradient) -->
                    <div class="rounded-3xl p-8 text-white shadow-xl relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-800">
                        <!-- Abstract Background Decoration -->
                        <div class="absolute inset-0 overflow-hidden pointer-events-none">
                            <div class="absolute -top-24 -right-12 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="absolute bottom-[-30%] left-[-10%] w-48 h-48 bg-cyan-400/20 rounded-full blur-2xl"></div>
                            <svg class="absolute top-0 right-0 p-4 opacity-[0.07] h-32 w-32" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        
                        <div class="relative z-10">
                            <p class="text-blue-100 text-sm font-semibold mb-2 tracking-wide uppercase">Available Balance</p>
                            <h2 class="text-5xl font-black mb-6 flex items-baseline gap-1 drop-shadow-md">
                                <span class="text-2xl text-blue-200">₱</span>
                                {{ Number(wallet.balance).toLocaleString() }}
                            </h2>
                            
                            <div class="flex items-center gap-2 text-xs font-bold text-white bg-white/10 backdrop-blur-md w-fit px-3.5 py-1.5 rounded-full border border-white/20 shadow-inner">
                                <span class="w-1.5 h-1.5 rounded-full" :class="wallet.status === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-red-400'"></span>
                                <span class="capitalize tracking-wide">{{ wallet.status }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Tabs -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex border-b border-gray-200 mb-6">
                            <button @click="activeTab = 'topup'" :class="['flex-1 py-2 font-bold text-sm transition', activeTab === 'topup' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']">
                                Top Up
                            </button>
                            <button @click="activeTab = 'withdraw'" :class="['flex-1 py-2 font-bold text-sm transition', activeTab === 'withdraw' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']">
                                Withdraw
                            </button>
                        </div>

                        <!-- Top-up Form -->
                        <form v-if="activeTab === 'topup'" @submit.prevent="submitTopup" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Add (PHP)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                    <input 
                                        type="number" 
                                        v-model="form.amount"
                                        min="100" 
                                        max="50000"
                                        required
                                        class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                                        placeholder="0.00"
                                    >
                                </div>
                                <p v-if="form.errors.amount" class="text-sm text-red-600 mt-1">{{ form.errors.amount }}</p>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Confirm Top Up
                            </button>
                        </form>

                        <!-- Withdraw Form -->
                        <form v-if="activeTab === 'withdraw'" @submit.prevent="submitWithdraw" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Withdraw (PHP)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                    <input 
                                        type="number" 
                                        v-model="withdrawForm.amount"
                                        min="100" 
                                        :max="wallet.balance"
                                        required
                                        class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                                        placeholder="0.00"
                                    >
                                </div>
                                <p v-if="withdrawForm.errors.amount" class="text-sm text-red-600 mt-1">{{ withdrawForm.errors.amount }}</p>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="withdrawForm.processing || wallet.balance < 100"
                                class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center disabled:opacity-50"
                            >
                                <svg v-if="withdrawForm.processing" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Withdraw to Bank
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Transactions -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900">Recent Transactions</h3>
                        </div>
                        
                        <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                            <div v-if="transactions.length === 0" class="flex flex-col items-center justify-center py-16 px-4 text-center">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <p class="text-gray-900 font-bold">It's quiet in here.</p>
                                <p class="text-sm text-gray-500 mt-1">Transactions will appear here once you start buying or selling.</p>
                            </div>
                            
                            <div v-for="tx in transactions" :key="tx.id" class="p-5 hover:bg-slate-50 transition-colors flex items-center justify-between group">
                                <div class="flex items-center gap-4">
                                    <!-- Dynamic Transaction Icon -->
                                    <div :class="[
                                        'h-12 w-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-105',
                                        Number(tx.amount) > 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-50 text-red-500'
                                    ]">
                                        <!-- specific icons based on tx type could be added here, falling back to generic arrows -->
                                        <svg v-if="tx.type === 'topup'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <svg v-else-if="tx.type === 'escrow_verified' || tx.type === 'escrow_release'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <svg v-else-if="Number(tx.amount) > 0" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                        <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="font-bold text-gray-900 text-sm md:text-base">{{ formatType(tx.type) }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-1 max-w-[200px] sm:max-w-md">{{ tx.description || 'No description' }}</p>
                                        <p class="text-[11px] font-medium text-gray-400 mt-1 uppercase tracking-wider">{{ new Date(tx.created_at).toLocaleString() }}</p>
                                    </div>
                                </div>
                                <div :class="[
                                    'font-black text-right pl-4',
                                    Number(tx.amount) > 0 ? 'text-emerald-600 text-lg' : 'text-gray-900 text-base'
                                ]">
                                    {{ Number(tx.amount) > 0 ? '+' : '' }}₱{{ Math.abs(Number(tx.amount)).toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import CourierLayout from '@/Layouts/CourierLayout.vue';

const page = usePage();

const layoutComponent = computed(() => {
    const role = page.props.auth?.user?.role;
    if (role === 'distributor') return OwnerLayout;
    if (role === 'courier') return CourierLayout;
    return MainLayout;
});

const props = defineProps({
    wallet: Object,
    transactions: Array,
});

const activeTab = ref('topup');

const form = useForm({
    amount: '',
});

const withdrawForm = useForm({
    amount: '',
});

const submitTopup = () => {
    form.post('/wallet/topup', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const submitWithdraw = () => {
    withdrawForm.post('/wallet/withdraw', {
        preserveScroll: true,
        onSuccess: () => withdrawForm.reset(),
    });
};

const formatType = (type) => {
    const map = {
        'topup': 'Wallet Top-up',
        'escrow_verified': 'Sale (payment verified)',
        'escrow_release': 'Escrow payout (on delivery)',
        'escrow_refund_clawback': 'Sale reversal (refund)',
        'withdrawal': 'Withdrawal',
        'payment': 'Payment Sent',
        'platform_fee': 'Platform Fee',
        'order_payment': 'Order payment',
        'order_refund': 'Refund',
    };
    return map[type] || String(type || '').replace(/_/g, ' ');
};
</script>
