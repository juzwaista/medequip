<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Wallet</h1>
                    <p class="text-gray-600 mt-1">Manage your funds and transaction history</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Balance and Top-up -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Balance Card -->
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-blue-100 text-sm font-medium mb-1">Available Balance</p>
                        <h2 class="text-4xl font-black mb-4 flex items-baseline gap-1">
                            <span class="text-2xl">₱</span>
                            {{ Number(wallet.balance).toLocaleString() }}
                        </h2>
                        
                        <div class="flex items-center gap-2 text-sm text-blue-100 bg-white/10 w-fit px-3 py-1.5 rounded-full border border-white/20">
                            <span class="w-2 h-2 rounded-full" :class="wallet.status === 'active' ? 'bg-green-400' : 'bg-red-400'"></span>
                            <span class="capitalize">{{ wallet.status }}</span>
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
                        
                        <div class="divide-y divide-gray-50 max-h-[600px] overflow-y-auto">
                            <div v-if="transactions.length === 0" class="p-12 text-center text-gray-500">
                                <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                No transactions yet.
                            </div>
                            
                            <div v-for="tx in transactions" :key="tx.id" class="p-5 hover:bg-gray-50 transition flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div :class="[
                                        'h-10 w-10 rounded-full flex items-center justify-center shrink-0',
                                        Number(tx.amount) > 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
                                    ]">
                                        <svg v-if="Number(tx.amount) > 0" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ formatType(tx.type) }}</p>
                                        <p class="text-xs text-gray-500">{{ tx.description || 'No description' }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ new Date(tx.created_at).toLocaleString() }}</p>
                                    </div>
                                </div>
                                <div :class="[
                                    'font-bold text-lg',
                                    Number(tx.amount) > 0 ? 'text-green-600' : 'text-gray-900'
                                ]">
                                    {{ Number(tx.amount) > 0 ? '+' : '' }}₱{{ Math.abs(Number(tx.amount)).toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';

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
        'escrow_release': 'Escrow Payout',
        'withdrawal': 'Withdrawal',
        'payment': 'Payment Sent',
        'platform_fee': 'Platform Fee',
    };
    return map[type] || type.replace('_', ' ');
};
</script>
