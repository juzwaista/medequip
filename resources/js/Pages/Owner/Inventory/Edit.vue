<template>
    <OwnerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <div class="flex items-center text-sm text-gray-600 mb-3">
                    <Link href="/owner/inventory" class="hover:text-blue-600">Inventory</Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 font-medium">Edit product</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Edit product</h1>
                <p class="text-gray-600 mt-2">Update photos, options, pricing, and stock.</p>
            </div>

            <div v-if="pageErrors && Object.keys(pageErrors).length > 0" class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4">
                <p class="text-sm font-medium text-red-800 mb-2">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    <li v-for="(err, key) in pageErrors" :key="key">{{ Array.isArray(err) ? err[0] : err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Gallery -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Photos</h2>
                    <p class="text-sm text-gray-600 mb-4">
                        Check images to remove, add more files, and pick the main display. At least one image must remain.
                    </p>

                    <div v-if="visibleExisting.length" class="flex flex-wrap gap-4 mb-6">
                        <div v-for="img in visibleExisting" :key="img.id" class="text-center">
                            <div class="relative w-28 h-28 rounded-xl border overflow-hidden">
                                <img :src="`/storage/${img.image_path}`" alt="" class="w-full h-full object-cover" />
                                <label
                                    class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] py-1 cursor-pointer"
                                >
                                    <input v-model="removedImageIds" type="checkbox" :value="img.id" class="mr-1 align-middle" />
                                    Remove
                                </label>
                            </div>
                            <label class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-700 cursor-pointer">
                                <input v-model="primaryPick" type="radio" :value="`e:${img.id}`" class="text-blue-600" />
                                Main
                            </label>
                        </div>
                    </div>

                    <label class="block text-sm font-semibold text-gray-800 mb-2">Add more images</label>
                    <input
                        type="file"
                        accept="image/*"
                        multiple
                        @change="onNewImages"
                        class="block w-full max-w-md text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700"
                    />
                    <div v-if="newPreviews.length" class="mt-4 flex flex-wrap gap-4">
                        <div v-for="(src, idx) in newPreviews" :key="'n' + idx" class="text-center">
                            <div class="relative w-28 h-28 rounded-xl border overflow-hidden">
                                <img :src="src" alt="" class="w-full h-full object-cover" />
                            </div>
                            <label class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-700 cursor-pointer">
                                <input v-model="primaryPick" type="radio" :value="`n:${idx}`" class="text-blue-600" />
                                Main
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Barcode -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Barcode</h2>
                    <div class="flex gap-2 max-w-xl">
                        <input v-model="fields.barcode" type="text" class="flex-1 px-4 py-3 border border-gray-300 rounded-xl font-mono text-sm" />
                        <BarcodeScannerModal @scanned="(code) => (fields.barcode = code)" />
                    </div>
                </section>

                <!-- SKU -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Product SKU</h2>
                    <div>
                        <input
                            v-model="fields.sku"
                            type="text"
                            placeholder="Optional (e.g., MED-001)"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl font-mono text-sm"
                        />
                        <p v-if="pageErrors.sku" class="text-red-600 text-sm mt-2">{{ pageErrors.sku }}</p>
                        <p class="text-xs text-gray-500 mt-2">This is the product identifier shown in inventory.</p>
                    </div>
                </section>

                <!-- Basics -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Basic information</h2>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Product name <span class="text-red-500">*</span></label>
                            <input v-model="fields.name" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Category <span class="text-red-500">*</span></label>
                            <select v-model="fields.category_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white">
                                <template v-for="group in categoryGroups" :key="group.parent.id">
                                    <optgroup :label="group.parent.name">
                                        <option :value="group.parent.id">{{ group.parent.name }} (general)</option>
                                        <option v-for="c in group.children" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                        <div v-if="isMedicineCategory" class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input v-model="fields.requires_prescription" type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600" />
                                <span class="text-sm font-medium text-gray-900">Requires a valid prescription</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Brand <span class="text-red-500">*</span></label>
                                <input v-model="fields.brand" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Model <span class="text-red-500">*</span></label>
                                <input v-model="fields.model" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Description <span class="text-red-500">*</span></label>
                            <textarea v-model="fields.description" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                    </div>
                </section>

                <!-- Pricing -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Retail (₱) <span class="text-red-500">*</span></label>
                            <input v-model.number="fields.base_price" type="number" step="0.01" min="0" required class="w-full px-4 py-3 border rounded-xl" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Wholesale (₱)</label>
                            <input v-model.number="fields.wholesale_price" type="number" step="0.01" min="0" class="w-full px-4 py-3 border rounded-xl" />
                        </div>
                        <div v-if="fields.wholesale_price" class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Min wholesale qty</label>
                            <input v-model.number="fields.wholesale_min_qty" type="number" min="1" class="w-full max-w-xs px-4 py-3 border rounded-xl" />
                        </div>
                    </div>
                </section>

                <!-- Variations -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Options / variations</h2>
                        <button type="button" class="text-sm font-semibold text-blue-600 hover:underline" @click="addVariationRow">+ Add option</button>
                    </div>
                    <div v-if="variations.length === 0" class="text-sm text-gray-500 italic mb-4">No variations — stock applies to the product as a whole.</div>
                    <div v-for="(row, i) in variations" :key="row._key" class="mb-4 p-4 rounded-xl border border-gray-100 bg-gray-50/80 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-500">Option {{ i + 1 }}</span>
                            <button type="button" class="text-xs text-red-600 font-semibold" @click="removeVariationRow(i)">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input v-model="row.option_name" type="text" placeholder="Option name" class="px-3 py-2 border rounded-lg text-sm" />
                            <input v-model="row.option_value" type="text" placeholder="Value" class="px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Price adjustment (±₱)</label>
                                <input
                                    v-model.number="row.price_adjustment"
                                    type="number"
                                    step="0.01"
                                    placeholder="0"
                                    class="px-3 py-2 border rounded-lg text-sm w-full"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Option SKU (optional)</label>
                                <input
                                    v-model="row.sku"
                                    type="text"
                                    placeholder="SKU (optional)"
                                    class="px-3 py-2 border rounded-lg text-sm w-full"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Stock for option *</label>
                                <input
                                    v-model.number="variationStocks[i]"
                                    type="number"
                                    :min="variantReserved(row)"
                                    placeholder="0"
                                    class="px-3 py-2 border rounded-lg text-sm w-full"
                                />
                                <p v-if="variantReserved(row) > 0" class="text-[11px] text-amber-800 mt-1">
                                    {{ variantReserved(row) }} reserved on open orders (minimum on-hand).
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Warranty / Expiry -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Warranty &amp; expiration</h2>
                    <div class="space-y-4">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-800">
                            <input v-model="fields.has_warranty" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                            Has warranty
                        </label>
                        <div v-if="fields.has_warranty">
                            <input
                                v-model.number="fields.warranty_months"
                                type="number"
                                min="1"
                                max="120"
                                class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-xl"
                                placeholder="Months"
                            />
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-800">
                            <input v-model="fields.has_expiry" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                            Has expiration
                        </label>
                        <div v-if="fields.has_expiry" class="grid grid-cols-1 gap-3 max-w-lg">
                            <input v-model="fields.expiry_date" type="date" class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            <input v-model="fields.batch_number" type="text" placeholder="Batch / lot number" class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                    </div>
                </section>

                <!-- Visibility + stock -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8 space-y-6">
                    <label class="inline-flex items-center gap-3 cursor-pointer">
                        <input v-model="fields.is_active" type="checkbox" class="h-5 w-5 rounded border-gray-300 text-blue-600" />
                        <span class="text-sm font-semibold text-gray-900">Product is active (visible to customers)</span>
                    </label>

                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Stock</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div v-if="variations.length === 0">
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Quantity on hand <span class="text-red-500">*</span></label>
                                <input
                                    v-model.number="fields.initial_quantity"
                                    type="number"
                                    :min="baseReserved"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl"
                                />
                                <p v-if="baseReserved > 0" class="text-xs text-amber-800 mt-1">
                                    {{ baseReserved }} unit(s) reserved on open orders (minimum on-hand).
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Low-stock alert at <span class="text-red-500">*</span></label>
                                <input v-model.number="fields.reorder_level" type="number" min="0" required class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-xl" />
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex-1 bg-blue-600 text-white px-6 py-3.5 rounded-xl font-bold hover:bg-blue-700 disabled:opacity-50 shadow-md"
                    >
                        {{ submitting ? 'Saving…' : 'Save changes' }}
                    </button>
                    <Link href="/owner/inventory" class="px-6 py-3.5 border-2 border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 text-center">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import BarcodeScannerModal from '@/Components/BarcodeScannerModal.vue';

const props = defineProps({
    product: Object,
    inventory: Object,
    variation_stocks: { type: Object, default: () => ({}) },
    variation_reserved: { type: Object, default: () => ({}) },
    base_reserved_quantity: { type: Number, default: 0 },
    categories: Array,
    medicine_category_ids: { type: Array, default: () => [] },
});

const baseReserved = computed(() => Math.max(0, Number(props.base_reserved_quantity ?? 0)));

function variantReserved(row) {
    if (row?.id == null) return 0;
    return Math.max(0, Number(props.variation_reserved?.[row.id] ?? 0));
}

const page = usePage();
const pageErrors = computed(() => page.props.errors || {});

function dateInput(val) {
    if (!val) return '';
    if (typeof val === 'string') return val.slice(0, 10);
    if (val.date) return String(val.date).slice(0, 10);
    return '';
}

const sortedVariations = computed(() =>
    [...(props.product.variations || [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
);

const fields = ref({
    name: props.product.name,
    category_id: props.product.category_id,
    brand: props.product.brand || '',
    model: props.product.model || '',
    sku: props.product.sku || '',
    requires_prescription: !!props.product.requires_prescription,
    description: props.product.description,
    base_price: props.product.base_price,
    wholesale_price: props.product.wholesale_price || '',
    wholesale_min_qty: props.product.wholesale_min_qty || '',
    has_warranty: props.product.has_warranty || false,
    warranty_months: props.product.warranty_months || '',
    has_expiry: props.product.has_expiry || false,
    expiry_date: dateInput(props.inventory?.expiry_date),
    batch_number: props.inventory?.batch_number || '',
    is_active: props.product.is_active,
    barcode: props.product.barcode || '',
    initial_quantity: 0,
    reorder_level: 10,
});

const baseInventoryRow = computed(
    () => props.product.inventory?.find((i) => i.product_variation_id == null) ?? props.inventory
);

let k = 0;
function rowKey() {
    k += 1;
    return `v-${k}`;
}

const variations = ref(
    sortedVariations.value.map((v) => ({
        _key: rowKey(),
        id: v.id,
        option_name: v.option_name,
        option_value: v.option_value,
        price_adjustment: v.price_adjustment ?? 0,
        sku: v.sku || '',
        is_active: v.is_active !== false,
    }))
);

const variationStocks = ref(
    sortedVariations.value.map((v) => {
        const n = Number(props.variation_stocks[v.id]);
        return Number.isFinite(n) ? n : 0;
    })
);

const removedImageIds = ref([]);
const newFiles = ref([]);
const newPreviews = ref([]);
const primaryPick = ref('');

const visibleExisting = computed(() => (props.product.images || []).filter((img) => !removedImageIds.value.includes(img.id)));

watch([visibleExisting, newPreviews, removedImageIds], () => {
    syncPrimaryDefault();
});

function syncPrimaryDefault() {
    const firstE = visibleExisting.value[0];
    const hasNew = newPreviews.value.length > 0;
    if (!firstE && !hasNew) {
        primaryPick.value = '';
        return;
    }
    const current = primaryPick.value;
    const stillValid =
        (current.startsWith('e:') && visibleExisting.value.some((img) => `e:${img.id}` === current)) ||
        (current.startsWith('n:') && hasNew && Number(current.slice(2)) < newPreviews.value.length);
    if (stillValid) return;
    if (firstE) primaryPick.value = `e:${firstE.id}`;
    else primaryPick.value = 'n:0';
}

onMounted(() => {
    const base = baseInventoryRow.value;
    if (!sortedVariations.value.length) {
        const q = Number(base?.quantity ?? 0);
        fields.value.initial_quantity = Number.isFinite(q) ? q : 0;
    }
    const rl = Number(base?.reorder_level ?? 10);
    fields.value.reorder_level = Number.isFinite(rl) ? rl : 10;
    syncPrimaryDefault();
});

const categoryGroups = computed(() => {
    const cats = props.categories || [];
    const parents = cats.filter((c) => !c.parent_id).sort((a, b) => a.name.localeCompare(b.name));
    return parents.map((parent) => ({
        parent,
        children: cats.filter((c) => c.parent_id === parent.id).sort((a, b) => a.name.localeCompare(b.name)),
    }));
});

const isMedicineCategory = computed(() => {
    const id = Number(fields.value.category_id);
    if (!id) return false;
    return (props.medicine_category_ids || []).map(Number).includes(id);
});

watch(isMedicineCategory, (on) => {
    if (!on) fields.value.requires_prescription = false;
});

function revokeNew() {
    newPreviews.value.forEach((url) => {
        if (url && url.startsWith('blob:')) URL.revokeObjectURL(url);
    });
}

onUnmounted(() => {
    revokeNew();
});

function onNewImages(e) {
    revokeNew();
    const files = Array.from(e.target.files || []).filter(Boolean);
    newFiles.value = files;
    newPreviews.value = files.map((f) => URL.createObjectURL(f));
}

function addVariationRow() {
    variations.value.push({
        _key: rowKey(),
        id: null,
        option_name: '',
        option_value: '',
        price_adjustment: 0,
        sku: '',
        is_active: true,
    });
    variationStocks.value.push(0);
}

function removeVariationRow(i) {
    variations.value.splice(i, 1);
    variationStocks.value.splice(i, 1);
}

const submitting = ref(false);

function appendBool(fd, key, val) {
    fd.append(key, val ? '1' : '0');
}

function submitForm() {
    submitting.value = true;

    const fd = new FormData();
    fd.append('_method', 'put');
    fd.append('name', fields.value.name);
    fd.append('description', fields.value.description);
    fd.append('category_id', String(fields.value.category_id || ''));
    fd.append('brand', fields.value.brand);
    fd.append('model', fields.value.model);
    fd.append('sku', fields.value.sku);
    appendBool(fd, 'requires_prescription', fields.value.requires_prescription);
    fd.append('base_price', String(fields.value.base_price ?? ''));
    if (fields.value.wholesale_price !== '' && fields.value.wholesale_price != null) {
        fd.append('wholesale_price', String(fields.value.wholesale_price));
    }
    if (fields.value.wholesale_min_qty !== '' && fields.value.wholesale_min_qty != null) {
        fd.append('wholesale_min_qty', String(fields.value.wholesale_min_qty));
    }
    appendBool(fd, 'has_warranty', fields.value.has_warranty);
    if (fields.value.has_warranty && fields.value.warranty_months) {
        fd.append('warranty_months', String(fields.value.warranty_months));
    }
    appendBool(fd, 'has_expiry', fields.value.has_expiry);
    appendBool(fd, 'is_active', fields.value.is_active);
    if (fields.value.barcode) fd.append('barcode', fields.value.barcode);

    removedImageIds.value.forEach((id) => fd.append('removed_image_ids[]', String(id)));
    newFiles.value.forEach((f) => fd.append('images[]', f));

    const varsPayload = variations.value.map((v) => {
        const payload = {
            option_name: v.option_name,
            option_value: v.option_value,
            price_adjustment: v.price_adjustment ?? 0,
            sku: v.sku || null,
            is_active: v.is_active,
        };
        if (v.id != null) payload.id = v.id;
        return payload;
    });
    fd.append('variations_json', JSON.stringify(varsPayload));
    fd.append('variation_stocks_json', JSON.stringify(variationStocks.value));
    fd.append('initial_quantity', String(fields.value.initial_quantity ?? 0));
    fd.append('reorder_level', String(fields.value.reorder_level ?? 10));
    if (fields.value.has_expiry && fields.value.expiry_date) fd.append('expiry_date', fields.value.expiry_date);
    if (fields.value.batch_number) fd.append('batch_number', fields.value.batch_number);

    if (primaryPick.value.startsWith('e:')) {
        fd.append('primary_image_id', primaryPick.value.slice(2));
    } else if (primaryPick.value.startsWith('n:') && newFiles.value.length) {
        fd.append('primary_image_index', primaryPick.value.slice(2));
    }

    router.post(`/owner/inventory/${props.product.id}`, fd, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>
