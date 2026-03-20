<template>
    <CourierLayout>
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center sticky top-0 z-10">
            <h1 class="text-lg font-bold tracking-tight text-gray-900">Courier Dashboard</h1>
            <div class="flex space-x-1 bg-gray-200 p-1 rounded-lg">
                <button @click="tab = 'active'" 
                    class="px-3 py-1.5 text-xs font-bold rounded-md transition"
                    :class="tab === 'active' ? 'bg-white shadow relative text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                    Active
                    <span v-if="myDeliveries.length > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full">{{ myDeliveries.length }}</span>
                </button>
                <button @click="tab = 'pool'" 
                    class="px-3 py-1.5 text-xs font-bold rounded-md transition"
                    :class="tab === 'pool' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                    Dispatch Pool
                </button>
            </div>
        </div>

        <div class="p-4 bg-gray-100 flex-1 overflow-y-auto">
            
            <!-- ACTIVE TAB -->
            <div v-if="tab === 'active'">
                <div v-if="myDeliveries.length > 0" class="space-y-4">
                    <div v-for="delivery in myDeliveries" :key="delivery.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center" 
                            :class="delivery.status === 'in_transit' ? 'bg-blue-50' : 'bg-gray-50'">
                            <div>
                                <span class="text-xs font-bold text-gray-500 uppercase">Tracking Number</span>
                                <p class="font-mono text-sm font-bold text-gray-900">{{ delivery.tracking_number }}</p>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-black rounded-lg uppercase tracking-wider"
                                :class="{
                                    'bg-blue-100 text-blue-800': delivery.status === 'in_transit',
                                    'bg-yellow-100 text-yellow-800': delivery.status === 'scheduled',
                                }">
                                {{ delivery.status.replace('_', ' ') }}
                            </span>
                        </div>
                        <div class="p-4 space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 font-medium mb-0.5">Pickup Location (Shop)</p>
                                <p class="text-sm font-semibold text-gray-900">{{ delivery.order.distributor.name }}</p>
                            </div>
                            
                            <div class="pl-4 border-l-2 border-gray-200">
                                <p class="text-xs text-gray-500 font-medium mb-0.5">Dropoff Location (Customer)</p>
                                <p class="text-sm font-semibold text-gray-900">{{ delivery.order.customer.name }}</p>
                                <p class="text-sm text-gray-600">{{ delivery.delivery_address }}</p>
                            </div>
                        </div>

                        <div class="p-3 bg-gray-50 flex space-x-2">
                            <Link v-if="delivery.status === 'scheduled'" :href="'/courier/scanner?action=pickup&order=' + delivery.order.order_number" class="flex-1 flex justify-center items-center bg-blue-600 text-white font-bold py-2 rounded-lg text-sm hover:bg-blue-700">
                                Scan Waybill
                            </Link>
                            <Link v-if="delivery.status === 'in_transit'" :href="'/courier/scanner?action=deliver&order=' + delivery.order.order_number" class="flex-1 flex justify-center items-center bg-green-500 text-white font-bold py-2 rounded-lg text-sm hover:bg-green-600">
                                Confirm Delivery
                            </Link>
                            <button v-if="delivery.status === 'in_transit'" @click="updateStatus(delivery.id, 'failed')" class="flex-1 bg-red-100 text-red-600 font-bold py-2 rounded-lg text-sm hover:bg-red-200">
                                Failed
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-12">
                    <div class="h-16 w-16 bg-white rounded-full mx-auto flex items-center justify-center shadow-sm mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <p class="text-gray-500 font-medium">No active deliveries.</p>
                    <p class="text-xs text-gray-400 mt-1">Check the dispatch pool to accept jobs.</p>
                </div>

                <div v-if="history.length > 0" class="mt-8">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 px-1">Recent History</h3>
                    <div class="space-y-3">
                        <div v-for="item in history" :key="item.id" class="bg-white p-3 rounded-lg shadow-sm border border-gray-100 flex justify-between items-center opacity-75">
                            <div>
                                <p class="text-xs font-bold text-gray-900">{{ item.tracking_number }}</p>
                                <p class="text-[10px] text-gray-500">{{ new Date(item.updated_at).toLocaleDateString() }}</p>
                            </div>
                            <span class="text-xs font-bold" :class="item.status === 'delivered' ? 'text-green-600' : 'text-red-500'">
                                {{ item.status.toUpperCase() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DISPATCH POOL TAB -->
            <div v-else>
                <div v-if="availableDeliveries.length > 0" class="space-y-4">
                    <div v-for="job in availableDeliveries" :key="job.id" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 transition hover:border-blue-300">
                        <div class="flex justify-between items-start mb-3">
                            <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest">Available Job</span>
                            <span class="text-xs text-gray-500 font-medium">{{ new Date(job.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-lg font-bold text-gray-900">{{ job.order.distributor.name }}</p>
                            <p class="text-sm text-gray-600 flex items-center mt-1">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ job.order.customer.name }} ({{ job.delivery_address }})
                            </p>
                        </div>

                        <button @click="acceptDelivery(job.id)" class="w-full bg-gray-900 text-white font-bold py-2.5 rounded-lg text-sm hover:bg-black transition shadow-sm">
                            Accept Delivery
                        </button>
                    </div>
                </div>
                <div v-else class="text-center py-12">
                    <div class="h-16 w-16 bg-white rounded-full mx-auto flex items-center justify-center shadow-sm mb-4">
                        <span class="text-2xl">🌍</span>
                    </div>
                    <p class="text-gray-500 font-medium">All clear!</p>
                    <p class="text-xs text-gray-400 mt-1">There are no pending deliveries in the pool.</p>
                </div>
            </div>

        </div>
    </CourierLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import CourierLayout from '@/Layouts/CourierLayout.vue';

const props = defineProps({
    availableDeliveries: Array,
    myDeliveries: Array,
    history: Array,
    courier: Object
});

const tab = ref('active');

const acceptDelivery = (id) => {
    router.post('/courier/deliveries/' + id + '/accept', {}, {
        preserveScroll: true,
        onSuccess: () => {
            tab.value = 'active';
        }
    });
};

const updateStatus = (id, status) => {
    if (confirm(`Are you sure you want to mark this delivery as ${status.replace('_', ' ')}?`)) {
        router.post('/courier/deliveries/' + id + '/status', { status }, {
            preserveScroll: true,
            onError: (errors) => {
                console.error("Delivery Status Update Error", errors);
                alert("Failed to update status: " + Object.values(errors).join(", "));
            }
        });
    }
};
</script>
