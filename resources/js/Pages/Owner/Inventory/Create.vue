<template>
    <OwnerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <div class="flex items-center text-sm text-gray-600 mb-3">
                    <Link href="/owner/inventory" class="hover:text-blue-600">Inventory</Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 font-medium">Add product</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Add product</h1>
                <p class="text-gray-600 mt-2">
                    Photos, variations, and stock are all configured here — one place for adding products.
                </p>
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
                    <p class="text-sm text-gray-600 mb-4">Upload one or more images. Choose which one is the main display.</p>
                    <input
                        type="file"
                        accept="image/*"
                        multiple
                        @change="onImagesChange"
                        class="block w-full max-w-md text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700"
                    />
                    <div v-if="imagePreviews.length" class="mt-4 flex flex-wrap gap-4">
                        <div v-for="(src, idx) in imagePreviews" :key="idx" class="text-center">
                            <div class="relative w-28 h-28 rounded-xl border overflow-hidden">
                                <img :src="src" alt="" class="w-full h-full object-cover" />
                            </div>
                            <label class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-700 cursor-pointer">
                                <input v-model.number="primaryImageIndex" type="radio" :value="idx" class="text-blue-600" />
                                Main
                            </label>
                        </div>
                    </div>
                    <p v-if="pageErrors.images" class="text-red-600 text-sm mt-2">{{ pageErrors.images }}</p>
                </section>

                <!-- Barcode -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Barcode</h2>
                    <div class="flex gap-2 max-w-xl">
                        <input
                            v-model="fields.barcode"
                            type="text"
                            placeholder="Scan or type"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-xl font-mono text-sm"
                        />
                        <BarcodeScannerModal @scanned="(code) => (fields.barcode = code)" />
                    </div>
                    <p v-if="pageErrors.barcode" class="text-red-600 text-sm mt-2">{{ pageErrors.barcode }}</p>
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
                        <p class="text-xs text-gray-500 mt-2">Leave empty to auto-generate.</p>
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
                                <option value="">Select a category</option>
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
                                <span class="text-sm font-medium text-gray-900">Requires a valid prescription (customer uploads after order)</span>
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
                            <textarea v-model="fields.description" required rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                    </div>
                </section>

                <!-- Pricing -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Retail (₱) <span class="text-red-500">*</span></label>
                            <input v-model.number="fields.base_price" type="number" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Wholesale (₱)</label>
                            <input v-model.number="fields.wholesale_price" type="number" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                        <div v-if="fields.wholesale_price" class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Min wholesale qty</label>
                            <input v-model.number="fields.wholesale_min_qty" type="number" min="1" class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                    </div>
                </section>

                <!-- Variations -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Options / variations</h2>
                        <button type="button" class="text-sm font-semibold text-blue-600 hover:underline" @click="addVariationRow">+ Add option</button>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Each row is an option (e.g. Color — Red). Set stock per option below.</p>
                    <div v-if="variations.length === 0" class="text-sm text-gray-500 italic">No variations — stock applies to the product as a whole.</div>
                    <div v-for="(row, i) in variations" :key="i" class="mb-4 p-4 rounded-xl border border-gray-100 bg-gray-50/80 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-500">Option {{ i + 1 }}</span>
                            <button type="button" class="text-xs text-red-600 font-semibold" @click="removeVariationRow(i)">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input v-model="row.option_name" type="text" placeholder="Option name (e.g. Color)" class="px-3 py-2 border rounded-lg text-sm" />
                            <input v-model="row.option_value" type="text" placeholder="Value (e.g. Red)" class="px-3 py-2 border rounded-lg text-sm" />
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
                                    min="0"
                                    placeholder="0"
                                    class="px-3 py-2 border rounded-lg text-sm w-full"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-if="pageErrors.variation_stocks_json" class="text-red-600 text-sm">{{ pageErrors.variation_stocks_json }}</p>
                </section>

                <!-- Warranty / Expiry -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Warranty &amp; expiration</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="rounded-xl border border-gray-100 p-4 bg-gray-50/80">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                <input v-model="fields.has_warranty" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                                Has warranty
                            </label>
                            <input
                                v-if="fields.has_warranty"
                                v-model.number="fields.warranty_months"
                                type="number"
                                min="1"
                                max="120"
                                placeholder="Months"
                                class="mt-3 w-full px-4 py-3 border border-gray-300 rounded-xl"
                            />
                        </div>
                        <div class="rounded-xl border border-gray-100 p-4 bg-gray-50/80">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                <input v-model="fields.has_expiry" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                                Has expiration
                            </label>
                            <input v-if="fields.has_expiry" v-model="fields.expiry_date" type="date" class="mt-3 w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            <input
                                v-if="fields.has_expiry"
                                v-model="fields.batch_number"
                                type="text"
                                placeholder="Batch / lot number"
                                class="mt-3 w-full px-4 py-3 border border-gray-300 rounded-xl"
                            />
                        </div>
                    </div>
                </section>

                <!-- Stock -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Stock</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div v-if="variations.length === 0">
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Quantity on hand <span class="text-red-500">*</span></label>
                            <input v-model.number="fields.initial_quantity" type="number" min="0" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Low-stock alert at <span class="text-red-500">*</span></label>
                            <input v-model.number="fields.reorder_level" type="number" min="0" required class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex-1 bg-blue-600 text-white px-6 py-3.5 rounded-xl font-bold hover:bg-blue-700 disabled:opacity-50 shadow-md"
                    >
                        {{ submitting ? 'Creating…' : 'Create product' }}
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
    categories: Array,
    medicine_category_ids: { type: Array, default: () => [] },
});

const page = usePage();
const pageErrors = computed(() => page.props.errors || {});

const fields = ref({
    name: '',
    description: '',
    category_id: '',
    brand: '',
    model: '',
    sku: '',
    requires_prescription: false,
    barcode: '',
    base_price: '',
    wholesale_price: '',
    wholesale_min_qty: '',
    has_warranty: false,
    warranty_months: '',
    has_expiry: false,
    expiry_date: '',
    batch_number: '',
    initial_quantity: 0,
    reorder_level: 10,
});

const imageFiles = ref([]);
const imagePreviews = ref([]);
const primaryImageIndex = ref(0);
const variations = ref([]);
const variationStocks = ref([]);
const submitting = ref(false);

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

function revokePreviews() {
    imagePreviews.value.forEach((url) => {
        if (url && url.startsWith('blob:')) URL.revokeObjectURL(url);
    });
}

onUnmounted(() => {
    revokePreviews();
});

function onImagesChange(event) {
    revokePreviews();
    const files = Array.from(event.target.files || []).filter(Boolean);
    imageFiles.value = files;
    imagePreviews.value = files.map((f) => URL.createObjectURL(f));
    primaryImageIndex.value = 0;
}

function addVariationRow() {
    variations.value.push({
        option_name: '',
        option_value: '',
        price_adjustment: 0,
        sku: '',
    });
    variationStocks.value.push(0);
}

function removeVariationRow(i) {
    variations.value.splice(i, 1);
    variationStocks.value.splice(i, 1);
}

function appendBool(fd, key, val) {
    fd.append(key, val ? '1' : '0');
}

function submitForm() {
    submitting.value = true;

    const fd = new FormData();
    fd.append('name', fields.value.name);
    fd.append('description', fields.value.description);
    fd.append('category_id', String(fields.value.category_id || ''));
    fd.append('brand', fields.value.brand);
    fd.append('model', fields.value.model);
    if (fields.value.sku) {
        fd.append('sku', fields.value.sku);
    }
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
    if (fields.value.barcode) fd.append('barcode', fields.value.barcode);

    imageFiles.value.forEach((file) => {
        fd.append('images[]', file);
    });

    const varsPayload = variations.value.map((v) => ({
        option_name: v.option_name,
        option_value: v.option_value,
        price_adjustment: v.price_adjustment ?? 0,
        sku: v.sku || null,
    }));

    fd.append('variations_json', JSON.stringify(varsPayload));
    fd.append('variation_stocks_json', JSON.stringify(variationStocks.value));
    fd.append('primary_image_index', String(primaryImageIndex.value));
    fd.append('initial_quantity', String(fields.value.initial_quantity ?? 0));
    fd.append('reorder_level', String(fields.value.reorder_level ?? 10));
    if (fields.value.has_expiry && fields.value.expiry_date) {
        fd.append('expiry_date', fields.value.expiry_date);
    }
    if (fields.value.batch_number) fd.append('batch_number', fields.value.batch_number);

    router.post('/owner/inventory', fd, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            submitting.value = false;
        },
    });
}

onMounted(() => {
    console.log('[InventoryCreate] mounted');
});
</script>
