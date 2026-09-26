<template>
    <Head title="Create Purchase Order" />

    <OwnerLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.procurement.index')" class="text-ink-faint hover:text-ink-soft transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <h2 class="text-xl font-bold text-ink">Create Purchase Order</h2>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Supplier Info -->
            <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden p-6">
                <h3 class="text-lg font-bold text-ink mb-4">Supplier Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-ink">Select Supplier <span class="text-red-500">*</span></label>
                        <select v-model="form.supplier_id" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand" required>
                            <option value="" disabled>-- Choose a Supplier --</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.supplier_id" class="mt-1 text-xs text-rose-600">{{ form.errors.supplier_id }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-ink">Expected Delivery Date</label>
                        <input v-model="form.expected_delivery_date" type="date" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand">
                        <div v-if="form.errors.expected_delivery_date" class="mt-1 text-xs text-rose-600">{{ form.errors.expected_delivery_date }}</div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-ink">Order Items</h3>
                    <button type="button" @click="addItem" class="text-sm font-medium text-brand hover:text-brand-dark flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div v-for="(item, index) in form.items" :key="index" class="flex flex-col md:flex-row gap-4 p-4 border border-line rounded-control bg-mist items-start md:items-end relative">
                        <button type="button" @click="removeItem(index)" class="absolute top-2 right-2 text-ink-faint hover:text-rose-600 p-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        
                        <div class="w-full md:w-1/2">
                            <label class="block text-xs font-medium text-ink">Product <span class="text-red-500">*</span></label>
                            <select v-model="item.product_id" @change="item.product_variation_id = ''" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand text-sm" required>
                                <option value="" disabled>Select Product</option>
                                <option v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }}<template v-if="product.stock !== null"> (In stock: {{ product.stock }})</template>
                                </option>
                            </select>
                            <div v-if="form.errors[`items.${index}.product_id`]" class="mt-1 text-xs text-rose-600">Required</div>

                            <!-- Products with options (piece / box of 10, sizes...) must say which one is ordered -->
                            <div v-if="productFor(item)?.variations.length" class="mt-2">
                                <label class="block text-xs font-medium text-ink">Option <span class="text-red-500">*</span></label>
                                <select v-model="item.product_variation_id" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand text-sm" required>
                                    <option value="" disabled>Choose which option you are restocking</option>
                                    <option v-for="v in productFor(item).variations" :key="v.id" :value="v.id">
                                        {{ v.label }} (In stock: {{ v.stock }}<template v-if="v.units_per_pack > 1"> · {{ v.units_per_pack }} pcs each</template>)
                                    </option>
                                </select>
                                <div v-if="form.errors[`items.${index}.product_variation_id`]" class="mt-1 text-xs text-rose-600">{{ form.errors[`items.${index}.product_variation_id`] }}</div>
                            </div>
                        </div>

                        <div class="w-full md:w-1/4">
                            <label class="block text-xs font-medium text-ink">Quantity<template v-if="unitLabelFor(item)"> (in {{ pluralize(unitLabelFor(item)) }})</template> <span class="text-red-500">*</span></label>
                            <input v-model.number="item.quantity_ordered" type="number" min="1" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand text-sm" required>
                        </div>
                        
                        <div class="w-full md:w-1/4">
                            <label class="block text-xs font-medium text-ink">Unit Cost (₱) <span class="text-red-500">*</span></label>
                            <input v-model.number="item.unit_cost" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand text-sm" required>
                        </div>
                    </div>
                </div>

                <div v-if="form.items.length === 0" class="text-center py-8 text-ink-soft text-sm border-2 border-dashed border-line rounded-control">
                    No items added yet. Click "Add Item" to start your purchase order.
                </div>
            </div>

            <!-- Notes & Submission -->
            <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden p-6">
                <div>
                    <label class="block text-sm font-medium text-ink">Notes / Instructions for Supplier</label>
                    <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-control border-line shadow-sm focus:border-brand focus:ring-brand"></textarea>
                </div>
                
                <div class="mt-8 flex justify-between items-center border-t border-line pt-6">
                    <div class="text-lg">
                        <span class="text-ink-soft">Estimated Total:</span>
                        <span class="font-bold text-ink ml-2">₱{{ estimatedTotal.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                    </div>
                    
                    <button type="submit" :disabled="form.processing || form.items.length === 0" class="bg-brand hover:bg-brand-dark text-white px-6 py-2.5 rounded-control font-bold shadow-sm transition-colors disabled:opacity-50">
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
    prefill: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    supplier_id: '',
    expected_delivery_date: '',
    notes: '',
    items: [
        { 
            product_id: props.prefill?.product_id || '', product_variation_id: '', 
            quantity_ordered: props.prefill?.qty ? Number(props.prefill.qty) : 1, 
            unit_cost: 0 
        }
    ]
});

function addItem() {
    form.items.push({ product_id: '', product_variation_id: '', quantity_ordered: 1, unit_cost: 0 });
}

// "box" -> "boxes", "piece" -> "pieces"
const pluralize = (word) => (/(s|x|z|ch|sh)$/i.test(word) ? `${word}es` : `${word}s`);

const productFor = (item) => props.products.find((p) => p.id === item.product_id) || null;

// What one ordered unit is called: the chosen option's unit (box, piece...) or the product's own.
const unitLabelFor = (item) => {
    const product = productFor(item);
    if (!product) return '';
    const variation = product.variations.find((v) => v.id === item.product_variation_id);
    return variation?.unit_label || product.unit_label || '';
};

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
