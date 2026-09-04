<template>
    <Head title="Purchase Orders" />

    <OwnerLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-xl font-bold text-gray-900">Purchase Orders</h2>
                <Link
                    :href="route('owner.procurement.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create PO
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PO Number</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Expected Delivery</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="po in purchaseOrders.data" :key="po.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ po.po_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                {{ po.supplier.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ po.expected_delivery_date ? new Date(po.expected_delivery_date).toLocaleDateString() : 'TBD' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ₱{{ Number(po.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                    :class="{
                                        'bg-gray-100 text-gray-800': po.status === 'draft',
                                        'bg-blue-100 text-blue-800': po.status === 'sent',
                                        'bg-yellow-100 text-yellow-800': po.status === 'partially_received',
                                        'bg-green-100 text-green-800': po.status === 'completed',
                                        'bg-rose-100 text-rose-800': po.status === 'cancelled',
                                    }">
                                    {{ po.status.toUpperCase().replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link :href="route('owner.procurement.show', po.id)" class="text-blue-600 hover:text-blue-900">
                                    View
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="purchaseOrders.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                No purchase orders found. Click "Create PO" to order goods from a supplier.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="purchaseOrders?.data?.length > 0" class="mt-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 px-6 py-4 border-t border-gray-100">
                <div class="text-sm text-gray-600 text-center sm:text-left">
                    Showing {{ purchaseOrders.from }} to {{ purchaseOrders.to }} of {{ purchaseOrders.total }} POs
                </div>
                <div class="flex flex-wrap justify-center sm:justify-end gap-2">
                    <template v-for="link in purchaseOrders.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{
                                'bg-blue-600 text-white border-blue-600': link.active,
                                'bg-white text-gray-700 hover:bg-gray-50 border-gray-300': !link.active,
                            }"
                            class="px-3 py-2.5 sm:py-2 border rounded-lg text-sm font-medium min-w-[40px] text-center touch-manipulation inline-flex items-center justify-center"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2.5 sm:py-2 border border-gray-200 rounded-lg text-sm font-medium min-w-[40px] text-center opacity-50 cursor-not-allowed bg-gray-50 text-gray-400 inline-flex items-center justify-center"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    purchaseOrders: Object,
});
</script>
