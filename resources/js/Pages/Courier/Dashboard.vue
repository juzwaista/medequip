<template>
    <CourierLayout>
        <!-- Header with tab switcher -->
        <div class="px-5 py-4 bg-gradient-to-r from-blue-700 to-blue-600 sticky top-0 z-10">
            <div class="flex justify-between items-center mb-3">
                <h1 class="text-lg font-bold text-white tracking-tight">Courier Dashboard</h1>
                <div class="flex items-center gap-2 bg-white/20 rounded-full px-3 py-1">
                    <span class="text-xs font-bold text-white">₱{{ earnings.wallet_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                    <span class="text-blue-200 text-xs">Wallet</span>
                </div>
            </div>
            <div class="flex space-x-1 bg-blue-800/40 p-1 rounded-xl">
                <button @click="tab = 'active'"
                    class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-lg transition relative"
                    :class="tab === 'active' ? 'bg-white text-blue-700 shadow' : 'text-blue-100 hover:bg-white/10'">
                    <span>Active</span>
                    <span v-if="myDeliveries.length > 0"
                        class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-black">
                        {{ myDeliveries.length }}
                    </span>
                </button>
                <button @click="tab = 'pool'"
                    class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-lg transition"
                    :class="tab === 'pool' ? 'bg-white text-blue-700 shadow' : 'text-blue-100 hover:bg-white/10'">
                    <span>Pool</span>
                    <span v-if="availableDeliveries.length > 0"
                        class="bg-green-400 text-green-900 text-[10px] px-1.5 py-0.5 rounded-full font-black">
                        {{ availableDeliveries.length }}
                    </span>
                </button>
                <button @click="tab = 'history'"
                    class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-lg transition"
                    :class="tab === 'history' ? 'bg-white text-blue-700 shadow' : 'text-blue-100 hover:bg-white/10'">
                    History
                </button>
            </div>
        </div>

        <div class="p-4 bg-gray-50 flex-1 overflow-y-auto">

            <!-- ===== ACTIVE TAB ===== -->
            <div v-if="tab === 'active'">
                <div v-if="myDeliveries.length > 0" class="space-y-4">
                    <div v-for="delivery in myDeliveries" :key="delivery.id"
                        class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <!-- Card Header -->
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center"
                            :class="delivery.status === 'in_transit' ? 'bg-blue-50' : 'bg-amber-50'">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tracking #</span>
                                <p class="font-mono text-sm font-bold text-gray-900">{{ delivery.tracking_number }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- COD Badge -->
                                <span v-if="delivery.order?.payment_method === 'cod'"
                                    class="bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-1 rounded-full uppercase tracking-wider border border-orange-200">
                                    💵 COD
                                </span>
                                <span class="px-2.5 py-1 text-xs font-black rounded-lg uppercase tracking-wider"
                                    :class="{
                                        'bg-blue-100 text-blue-800': delivery.status === 'in_transit',
                                        'bg-amber-100 text-amber-800': delivery.status === 'scheduled',
                                    }">
                                    {{ delivery.status.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>

                        <!-- Addresses -->
                        <div class="p-4 space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Pickup</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ delivery.order?.distributor?.company_name }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 pl-1 border-l-2 border-dashed border-gray-200 ml-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 -ml-4">
                                    <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Dropoff</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ delivery.order?.customer?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ delivery.delivery_address }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- COD Instruction Banner -->
                        <div v-if="delivery.order?.payment_method === 'cod' && delivery.status === 'in_transit'"
                            class="mx-4 mb-3 p-3 bg-orange-50 border border-orange-200 rounded-xl flex items-start gap-2">
                            <span class="text-lg">💵</span>
                            <div>
                                <p class="text-xs font-bold text-orange-800">Cash on Delivery</p>
                                <p class="text-xs text-orange-700 mt-0.5">
                                    Collect <strong>₱{{ Number(delivery.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> from the customer on delivery.
                                    Remit the full amount to the distributor afterwards.
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="p-3 bg-gray-50 flex gap-2">
                            <button v-if="delivery.status === 'scheduled'"
                                @click="updateStatus(delivery.id, 'in_transit')"
                                class="flex-1 bg-blue-600 text-white font-bold py-2.5 rounded-xl text-sm hover:bg-blue-700 transition">
                                Start Delivery
                            </button>
                            <button v-if="delivery.status === 'in_transit'"
                                @click="confirmDelivery(delivery)"
                                class="flex-1 bg-green-600 text-white font-bold py-2.5 rounded-xl text-sm hover:bg-green-700 transition">
                                {{ delivery.order?.payment_method === 'cod' ? '💵 Confirm Delivery & Cash Collected' : 'Confirm Delivery' }}
                            </button>
                            <button v-if="delivery.status === 'in_transit'"
                                @click="updateStatus(delivery.id, 'failed')"
                                class="flex-none bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-xl text-sm hover:bg-red-200 transition">
                                Failed
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-16">
                    <div class="w-16 h-16 bg-blue-50 rounded-full mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <p class="text-gray-500 font-semibold">No active deliveries</p>
                    <p class="text-xs text-gray-400 mt-1">Check the dispatch pool to pick up jobs.</p>
                </div>
            </div>

            <!-- ===== DISPATCH POOL TAB ===== -->
            <div v-else-if="tab === 'pool'">
                <div v-if="availableDeliveries.length > 0" class="space-y-4">
                    <div v-for="job in availableDeliveries" :key="job.id"
                        class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 hover:border-blue-300 transition">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <span class="bg-green-100 text-green-800 text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider">Available</span>
                                <span v-if="job.order?.payment_method === 'cod'"
                                    class="bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-1 rounded-full border border-orange-200">
                                    💵 COD
                                </span>
                            </div>
                            <span class="text-xs text-gray-400 font-medium">{{ new Date(job.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                        </div>
                        <p class="text-base font-bold text-gray-900 mb-1">{{ job.order?.distributor?.company_name }}</p>
                        <p class="text-sm text-gray-500 mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9z" clip-rule="evenodd"/></svg>
                            {{ job.order?.customer?.name }} — {{ job.delivery_address }}
                        </p>
                        <div v-if="job.order?.payment_method === 'cod'" class="mb-3 text-xs text-orange-700 bg-orange-50 rounded-lg px-3 py-2 border border-orange-100">
                            Collect <strong>₱{{ Number(job.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> cash from customer on delivery
                        </div>
                        <button @click="acceptDelivery(job.id)"
                            class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl text-sm hover:bg-black transition shadow-sm">
                            Accept Job
                        </button>
                    </div>
                </div>
                <div v-else class="text-center py-16">
                    <div class="w-16 h-16 bg-green-50 rounded-full mx-auto flex items-center justify-center mb-4">
                        <span class="text-2xl">🌍</span>
                    </div>
                    <p class="text-gray-500 font-semibold">All clear!</p>
                    <p class="text-xs text-gray-400 mt-1">No pending deliveries in the pool right now.</p>
                </div>
            </div>

            <!-- ===== HISTORY TAB ===== -->
            <div v-else-if="tab === 'history'">
                <!-- Earnings Summary Cards -->
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Balance</p>
                        <p class="text-lg font-black text-blue-600">₱{{ earnings.wallet_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">All Time</p>
                        <p class="text-lg font-black text-green-600">₱{{ earnings.total_earned.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Deliveries</p>
                        <p class="text-lg font-black text-gray-800">{{ earnings.total_deliveries }}</p>
                    </div>
                </div>

                <!-- History List -->
                <div v-if="history.data?.length > 0" class="space-y-3">
                    <div v-for="item in history.data" :key="item.id"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-4 flex justify-between items-start">
                            <div class="flex-1 min-w-0 pr-3">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-xs font-bold text-gray-900 font-mono">{{ item.tracking_number }}</p>
                                    <span v-if="item.order?.payment_method === 'cod'"
                                        class="text-[9px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-bold border border-orange-200">COD</span>
                                </div>
                                <p class="text-xs text-gray-500 truncate">{{ item.order?.customer?.name }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">{{ item.order?.distributor?.company_name }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="inline-block text-[10px] font-black px-2 py-1 rounded-full uppercase"
                                    :class="item.status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                    {{ item.status }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-1">{{ item.actual_delivery_at ? new Date(item.actual_delivery_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—' }}</p>
                                <p v-if="item.courier_fee && item.status === 'delivered'" class="text-xs font-bold text-green-600 mt-1">+₱{{ Number(item.courier_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                        </div>
                        <!-- COD remittance status bar -->
                        <div v-if="item.order?.payment_method === 'cod' && item.status === 'delivered'"
                            class="px-4 py-2 border-t"
                            :class="item.cod_remitted_at ? 'bg-green-50 border-green-100' : 'bg-orange-50 border-orange-100'">
                            <p class="text-[10px] font-bold"
                                :class="item.cod_remitted_at ? 'text-green-700' : 'text-orange-700'">
                                {{ item.cod_remitted_at ? '✓ Cash remitted to distributor' : '⏳ Pending: Remit ₱' + Number(item.cod_amount || item.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' to distributor' }}
                            </p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="history.last_page > 1" class="flex justify-center gap-2 py-4">
                        <button v-if="history.prev_page_url"
                            @click="goToPage(history.current_page - 1)"
                            class="px-4 py-2 text-sm font-bold bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                            ← Prev
                        </button>
                        <span class="px-4 py-2 text-sm text-gray-500 font-medium">{{ history.current_page }} / {{ history.last_page }}</span>
                        <button v-if="history.next_page_url"
                            @click="goToPage(history.current_page + 1)"
                            class="px-4 py-2 text-sm font-bold bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                            Next →
                        </button>
                    </div>
                </div>
                <div v-else class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-gray-500 font-semibold">No delivery history yet</p>
                    <p class="text-xs text-gray-400 mt-1">Completed deliveries will appear here.</p>
                </div>
            </div>

        </div>
    </CourierLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CourierLayout from '@/Layouts/CourierLayout.vue';

const props = defineProps({
    availableDeliveries: Array,
    myDeliveries: Array,
    history: Object,
    courier: Object,
    earnings: Object,
});

const tab = ref('active');

const acceptDelivery = (id) => {
    router.post('/courier/deliveries/' + id + '/accept', {}, {
        preserveScroll: true,
        onSuccess: () => { tab.value = 'active'; }
    });
};

const updateStatus = (id, status) => {
    router.post('/courier/deliveries/' + id + '/status', { status }, { preserveScroll: true });
};

const confirmDelivery = (delivery) => {
    const isCod = delivery.order?.payment_method === 'cod';
    const amount = delivery.order?.total_amount;
    let msg = 'Confirm this delivery as completed?';
    if (isCod) {
        msg = `Confirm delivery completed?\n\nYou must collect ₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })} cash from the customer.\n\nYou will need to remit this amount to the distributor.`;
    }
    if (confirm(msg)) {
        updateStatus(delivery.id, 'delivered');
    }
};

const goToPage = (page) => {
    router.get('/courier/dashboard', { page }, { preserveScroll: true, preserveState: true });
};
</script>
