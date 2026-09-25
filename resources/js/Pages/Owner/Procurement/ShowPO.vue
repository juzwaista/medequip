<template>
    <Head :title="`PO ${po.po_number}`" />

    <OwnerLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.procurement.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-gray-900">Purchase Order: {{ po.po_number }}</h2>
                    <span class="ml-2 px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" 
                        :class="{
                            'bg-gray-100 text-gray-800': po.status === 'draft',
                            'bg-blue-100 text-blue-800': po.status === 'sent',
                            'bg-yellow-100 text-yellow-800': po.status === 'partially_received',
                            'bg-green-100 text-green-800': po.status === 'completed',
                            'bg-rose-100 text-rose-800': po.status === 'cancelled',
                        }">
                        {{ po.status.toUpperCase().replace('_', ' ') }}
                    </span>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <button v-if="po.status === 'draft'" @click="updateStatus('sent')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Send to Supplier
                    </button>
                    
                    <button v-if="['sent', 'partially_received'].includes(po.status)" @click="updateStatus('completed')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Receive All Remaining
                    </button>

                    <button v-if="['draft', 'sent', 'partially_received'].includes(po.status)" @click="updateStatus('cancelled')" class="bg-white border border-gray-300 text-rose-600 hover:bg-rose-50 px-4 py-2 rounded-lg font-medium shadow-sm transition-colors">
                        Cancel PO
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Items Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900">Order Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Unit Cost</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty Ordered</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty Received</th>
                                    <th v-if="canReceive" scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Receive now</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in po.items" :key="item.id">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ item.product.name }}
                                        <p v-if="item.product_variation" class="text-xs font-normal text-blue-700">{{ item.product_variation.display_label }}</p>
                                        <p v-if="item.units_per_pack > 1" class="text-xs font-normal text-gray-500">Ordered per {{ item.unit_label }} · {{ item.units_per_pack }} pcs each</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        ₱{{ Number(item.unit_cost).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">
                                        {{ item.quantity_ordered }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        <span v-if="po.status === 'completed'" class="text-green-600 font-bold">{{ item.quantity_received }}</span>
                                        <span v-else>{{ item.quantity_received }}</span>
                                    </td>
                                    <td v-if="canReceive" class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <input
                                            v-model.number="receiveQty[item.id]"
                                            type="number"
                                            min="0"
                                            :max="remaining(item)"
                                            :disabled="remaining(item) === 0"
                                            class="w-24 rounded-md border-gray-300 text-sm text-right disabled:bg-gray-100"
                                        />
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ remaining(item) }} {{ plural(item.unit_label, remaining(item)) }} outstanding</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                        ₱{{ (item.unit_cost * item.quantity_ordered).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td :colspan="canReceive ? 5 : 4" class="px-6 py-4 text-right text-sm font-bold text-gray-900 uppercase">Grand Total</td>
                                    <td class="px-6 py-4 text-right text-lg font-bold text-blue-600">
                                        ₱{{ Number(po.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div v-if="canReceive" class="flex justify-end -mt-2">
                    <button @click="receiveDelivery" :disabled="!hasDelivery" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Record delivery
                    </button>
                </div>

                <!-- Notes -->
                <div v-if="po.notes" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Notes & Instructions</h3>
                    <p class="text-gray-600 text-sm whitespace-pre-wrap">{{ po.notes }}</p>
                </div>
            </div>

            <!-- Right Column: Info Sidebar -->
            <div class="space-y-6">
                <!-- Supplier Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Supplier Details</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500">Company Name</p>
                            <p class="font-medium text-gray-900">{{ po.supplier.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Contact Person</p>
                            <p class="text-gray-900">{{ po.supplier.contact_person || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email Address</p>
                            <p class="text-blue-600">{{ po.supplier.email || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Phone</p>
                            <p class="text-gray-900">{{ po.supplier.phone || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Order Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Order Information</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500">PO Number</p>
                            <p class="font-bold text-gray-900">{{ po.po_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Created At</p>
                            <p class="text-gray-900">{{ new Date(po.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Expected Delivery Date</p>
                            <p class="text-gray-900 font-medium">{{ po.expected_delivery_date ? new Date(po.expected_delivery_date).toLocaleDateString() : 'TBD' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    po: Object,
});

const canReceive = computed(() => ['sent', 'partially_received'].includes(props.po.status));

// Units arriving in this delivery, per item id.
const receiveQty = reactive({});
props.po.items.forEach((item) => { receiveQty[item.id] = 0; });

const remaining = (item) => Math.max(0, item.quantity_ordered - item.quantity_received);
const hasDelivery = computed(() => props.po.items.some((item) => Number(receiveQty[item.id]) > 0));

const plural = (word, n) => (n === 1 ? word : (/(s|x|z|ch|sh)$/i.test(word) ? `${word}es` : `${word}s`));

function receiveDelivery() {
    if (!confirm('Record this delivery and add the received units to your live inventory?')) return;

    router.post(route('owner.procurement.receive', props.po.id), { quantities: { ...receiveQty } }, {
        preserveScroll: true,
        onSuccess: () => props.po.items.forEach((item) => { receiveQty[item.id] = 0; }),
    });
}

function updateStatus(newStatus) {
    let msg = `Are you sure you want to change the status to ${newStatus}?`;
    if (newStatus === 'sent') {
        msg = "This will formally send the Purchase Order. An automated email will be sent to the supplier if an email is provided. Proceed?";
    } else if (newStatus === 'completed') {
        msg = "WARNING: This will add every unit still outstanding on this PO to your live inventory and close it. Only do this if the whole delivery has arrived. Use \"Record delivery\" for a partial delivery. Proceed?";
    }

    if (confirm(msg)) {
        router.post(route('owner.procurement.status', props.po.id), { status: newStatus }, {
            preserveScroll: true
        });
    }
}
</script>
