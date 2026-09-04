<template>
    <Head title="Create Purchase Order" />

    <OwnerLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.procurement.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-gray-900">Create Purchase Order</h2>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Supplier Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Supplier Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Select Supplier <span class="text-red-500">*</span></label>
                        <select v-model="form.supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>-- Choose a Supplier --</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.supplier_id" class="mt-1 text-xs text-rose-600">{{ form.errors.supplier_id }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expected Delivery Date</label>
                        <input v-model="form.expected_delivery_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <div v-if="form.errors.expected_delivery_date" class="mt-1 text-xs text-rose-600">{{ form.errors.expected_delivery_date }}</div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Order Items</h3>
                    <button type="button" @click="addItem" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div v-for="(item, index) in form.items" :key="index" class="flex flex-col md:flex-row gap-4 p-4 border border-gray-200 rounded-lg bg-gray-50 items-start md:items-end relative">
                        <button type="button" @click="removeItem(index)" class="absolute top-2 right-2 text-gray-400 hover:text-rose-600 p-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        
                        <div class="w-full md:w-1/2">
                            <label class="block text-xs font-medium text-gray-700">Product <span class="text-red-500">*</span></label>
                            <select v-model="item.product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" required>
                                <option value="" disabled>Select Product</option>
                                <option v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }} (Current Stock: {{ product.inventory?.quantity || 0 }})
                                </option>
                            </select>
                            <div v-if="form.errors[`items.${index}.product_id`]" class="mt-1 text-xs text-rose-600">Required</div>
                        </div>

                        <div class="w-full md:w-1/4">
                            <label class="block text-xs font-medium text-gray-700">Quantity <span class="text-red-500">*</span></label>
                            <input v-model.number="item.quantity_ordered" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" required>
                        </div>
                        
                        <div class="w-full md:w-1/4">
                            <label class="block text-xs font-medium text-gray-700">Unit Cost (₱) <span class="text-red-500">*</span></label>
                            <input v-model.number="item.unit_cost" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" required>
                        </div>
                    </div>
                </div>

                <div v-if="form.items.length === 0" class="text-center py-8 text-gray-500 text-sm border-2 border-dashed border-gray-300 rounded-lg">
                    No items added yet. Click "Add Item" to start your purchase order.
                </div>
            </div>

            <!-- Notes & Submission -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes / Instructions for Supplier</label>
                    <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mt-8 flex justify-between items-center border-t border-gray-100 pt-6">
                    <div class="text-lg">
                        <span class="text-gray-500">Estimated Total:</span>
                        <span class="font-bold text-gray-900 ml-2">₱{{ estimatedTotal.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                    </div>
                    
                    <button type="submit" :disabled="form.processing || form.items.length === 0" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-sm transition-colors disabled:opacity-50">
                        Save as Draft PO
                    </button>
                </div>
            </div>
        </form>
    </OwnerLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    suppliers: Array,
    products: Array,
});

const form = useForm({
    supplier_id: '',
    expected_delivery_date: '',
    notes: '',
    items: [
        { product_id: '', quantity_ordered: 1, unit_cost: 0 }
    ]
});

function addItem() {
    form.items.push({ product_id: '', quantity_ordered: 1, unit_cost: 0 });
}

function removeItem(index) {
    form.items.splice(index, 1);
}

const estimatedTotal = computed(() => {
    return form.items.reduce((total, item) => {
        const qty = parseFloat(item.quantity_ordered) || 0;
        const cost = parseFloat(item.unit_cost) || 0;
        return total + (qty * cost);
    }, 0);
});

function submit() {
    form.post(route('owner.procurement.store'));
}
</script>
