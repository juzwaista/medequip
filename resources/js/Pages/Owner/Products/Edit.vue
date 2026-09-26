<template>
    <OwnerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-ink">Edit product</h1>
                <p class="text-ink-soft mt-2">Update your product listing details.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Media -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Product images</h2>
                    <p class="text-sm text-ink-soft mb-4">Mark images to remove, or add new ones (max 12 total after changes). Keep at least one image.</p>

                    <div v-if="legacyImageUrl" class="mb-4 p-4 rounded-card bg-amber-50 border border-amber-200">
                        <p class="text-sm font-medium text-amber-900 mb-2">Legacy cover image</p>
                        <img :src="legacyImageUrl" alt="" class="w-24 h-24 object-cover rounded-control border" />
                    </div>

                    <div class="flex flex-wrap gap-3 mb-6">
                        <div
                            v-for="img in product.images"
                            :key="img.id"
                            class="relative w-28 h-28 rounded-card border overflow-hidden"
                            :class="removedImageIds.includes(img.id) ? 'border-red-300 opacity-60' : 'border-line'"
                        >
                            <img :src="img.url || `/storage/${img.image_path}`" alt="" class="w-full h-full object-cover" />
                            <button
                                v-if="!removedImageIds.includes(img.id)"
                                type="button"
                                @click="removedImageIds.push(img.id)"
                                class="absolute top-1 right-1 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-control shadow"
                            >
                                Remove
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="removedImageIds = removedImageIds.filter((id) => id !== img.id)"
                                class="absolute inset-0 bg-black/50 text-white text-xs font-semibold flex items-center justify-center"
                            >
                                Undo
                            </button>
                        </div>
                    </div>

                    <label class="block text-sm font-semibold text-ink mb-2">Add more images</label>
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        @change="onImages"
                        class="block w-full text-sm text-ink-soft file:mr-4 file:py-2.5 file:px-4 file:rounded-control file:border-0 file:text-sm file:font-semibold file:bg-brand-tint file:text-brand-dark hover:file:bg-brand-tint"
                    />
                    <p v-if="form.errors.images" class="text-red-600 text-sm mt-2">{{ form.errors.images }}</p>

                    <div v-if="newImagePreviews.length" class="mt-4 flex flex-wrap gap-3">
                        <div v-for="(p, idx) in newImagePreviews" :key="p.key" class="relative w-24 h-24 rounded-card border overflow-hidden">
                            <img :src="p.url" alt="" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                @click="removeNewImage(idx)"
                                class="absolute inset-0 bg-black/50 text-white text-xs font-semibold opacity-0 hover:opacity-100 transition flex items-center justify-center"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Basics -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Basic information</h2>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Product name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required class="w-full px-4 py-3 border border-line rounded-card" />
                            <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Category <span class="text-red-500">*</span></label>
                            <select
                                v-model="form.category_id"
                                required
                                class="w-full px-4 py-3 border border-line rounded-card bg-white"
                            >
                                <template v-for="group in categoryGroups" :key="group.parent.id">
                                    <optgroup :label="group.parent.name">
                                        <option :value="group.parent.id">{{ group.parent.name }} (general)</option>
                                        <option v-for="c in group.children" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Brand <span class="text-red-500">*</span></label>
                                <input v-model="form.brand" type="text" required class="w-full px-4 py-3 border border-line rounded-card" />
                                <p v-if="form.errors.brand" class="text-red-600 text-sm mt-1">{{ form.errors.brand }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Model <span class="text-red-500">*</span></label>
                                <input v-model="form.model" type="text" required class="w-full px-4 py-3 border border-line rounded-card" />
                                <p v-if="form.errors.model" class="text-red-600 text-sm mt-1">{{ form.errors.model }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Medicine -->
                <section
                    v-if="isMedicineCategory"
                    class="rounded-card border border-brand-soft bg-brand-tint/40 shadow-sm p-6 sm:p-8"
                >
                    <h2 class="text-xs font-bold   text-brand-dark mb-2">Medicine compliance</h2>
                    <p class="text-sm text-brand-dark/80 mb-4">
                        When enabled, the customer uploads a prescription after placing the order. You review it before they can pay.
                    </p>
                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.requires_prescription"
                                type="checkbox"
                                class="mt-1 h-5 w-5 rounded border-line text-brand focus:ring-brand"
                            />
                            <span class="text-sm font-medium text-ink">Requires a valid prescription to purchase</span>
                        </label>
                    </div>
                </section>

                <section class="rounded-card border border-amber-200 bg-amber-50/40 shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-amber-800 mb-2">Tax compliance</h2>
                    <p class="text-sm text-amber-900/80 mb-4">
                        Identify items that are exempt from the standard 12% VAT in the Philippines.
                    </p>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            v-model="form.is_vat_exempt"
                            type="checkbox"
                            class="mt-1 h-5 w-5 rounded border-line text-amber-600 focus:ring-amber-500"
                        />
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-ink">VAT Exempt</span>
                            <span class="text-xs text-ink-soft">This product will be calculated without the 12% VAT extraction.</span>
                        </div>
                    </label>
                </section>

                <!-- Description -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Description</h2>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        required
                        class="w-full px-4 py-3 border border-line rounded-card"
                    />
                </section>

                <!-- Pricing -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Retail price (₱) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-soft">₱</span>
                                <input v-model="form.base_price" type="number" step="0.01" min="0" required class="w-full pl-9 pr-4 py-3 border rounded-card" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Wholesale price (₱)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-soft">₱</span>
                                <input v-model="form.wholesale_price" type="number" step="0.01" min="0" class="w-full pl-9 pr-4 py-3 border rounded-card" />
                            </div>
                        </div>
                        <div v-if="form.wholesale_price" class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-ink mb-1.5">Minimum wholesale quantity</label>
                            <input v-model="form.wholesale_min_qty" type="number" min="1" class="w-full max-w-xs px-4 py-3 border rounded-card" />
                        </div>
                    </div>
                </section>

                <!-- Status -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <label class="inline-flex items-center gap-3 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-line text-brand" />
                        <span class="text-sm font-semibold text-ink">Product is active (visible in catalog)</span>
                    </label>
                </section>

                <!-- Variations -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-xs font-bold   text-ink-soft">Variations</h2>
                            <p class="text-sm text-ink-soft mt-1">Define size, color, or other options for this product.</p>
                        </div>
                        <button
                            type="button"
                            @click="addVariation"
                            class="text-sm font-semibold text-brand px-3 py-1.5 rounded-control border border-brand-soft bg-brand-tint"
                        >
                            + Add option
                        </button>
                    </div>
                    <div v-for="(row, idx) in variations" :key="idx" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4 p-4 rounded-card bg-mist border border-line">
                        <div class="md:col-span-2 flex items-end pb-2">
                            <label class="inline-flex items-center gap-2 text-xs text-ink">
                                <input v-model="row.is_active" type="checkbox" class="rounded border-line" />
                                Active
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-ink-soft">Option</label>
                            <input v-model="row.option_name" class="mt-1 w-full px-3 py-2 border rounded-control text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-ink-soft">Value</label>
                            <input v-model="row.option_value" class="mt-1 w-full px-3 py-2 border rounded-control text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-ink-soft">Price ± ₱</label>
                            <input v-model.number="row.price_adjustment" type="number" step="0.01" class="mt-1 w-full px-3 py-2 border rounded-control text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-xs font-medium text-ink-soft">Variation SKU (optional)</label>
                            <input v-model="row.sku" class="mt-1 w-full px-3 py-2 border rounded-control text-sm font-mono" />
                        </div>
                        <div class="md:col-span-1 flex items-end justify-end">
                            <button type="button" @click="removeVariation(idx)" class="text-red-600 p-2 hover:bg-red-50 rounded-control">✕</button>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 bg-brand text-white px-6 py-3.5 rounded-card font-bold hover:bg-brand-dark disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                    <Link href="/owner/products" class="px-6 py-3.5 border-2 border-line rounded-card font-semibold text-ink hover:bg-mist text-center">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    product: Object,
    categories: Array,
    medicine_category_ids: { type: Array, default: () => [] },
});

const variations = ref(
    (props.product.variations || []).map((v) => ({
        id: v.id,
        option_name: v.option_name,
        option_value: v.option_value,
        price_adjustment: v.price_adjustment ?? 0,
        sku: v.sku || '',
        is_active: v.is_active !== false,
    }))
);

const removedImageIds = ref([]);
const newImagePreviews = ref([]);

const legacyImageUrl = computed(() => {
    if (props.product.images?.length) return null;
    return props.product.image_path ? `/storage/${props.product.image_path}` : null;
});

const form = useForm({
    _method: 'put',
    name: props.product.name,
    category_id: props.product.category_id,
    brand: props.product.brand || '',
    model: props.product.model || '',
    description: props.product.description,
    base_price: props.product.base_price,
    wholesale_price: props.product.wholesale_price || '',
    wholesale_min_qty: props.product.wholesale_min_qty || '',
    is_active: props.product.is_active,
    requires_prescription: !!props.product.requires_prescription,
    is_vat_exempt: !!props.product.is_vat_exempt,
    images: [],
    removed_image_ids: [],
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
    const id = Number(form.category_id);
    if (!id) return false;
    return (props.medicine_category_ids || []).map(Number).includes(id);
});

watch(isMedicineCategory, (on) => {
    if (!on) form.requires_prescription = false;
});

const onImages = (e) => {
    const picked = Array.from(e.target.files || []);
    const merged = [...(form.images || []), ...picked].slice(0, 12);
    form.images = merged;
    newImagePreviews.value = merged.map((file, i) => ({
        key: `${file.name}-${i}-${file.size}`,
        url: URL.createObjectURL(file),
    }));
    e.target.value = '';
};

const removeNewImage = (idx) => {
    const next = [...form.images];
    next.splice(idx, 1);
    form.images = next;
    newImagePreviews.value = next.map((file, i) => ({
        key: `${file.name}-${i}-${file.size}`,
        url: URL.createObjectURL(file),
    }));
};

const addVariation = () => {
    variations.value.push({
        option_name: 'Color',
        option_value: '',
        price_adjustment: 0,
        sku: '',
        is_active: true,
    });
};

const removeVariation = (idx) => {
    variations.value.splice(idx, 1);
};

const submit = () => {
    form.removed_image_ids = removedImageIds.value;
    form.transform((data) => ({
        ...data,
        requires_prescription: data.requires_prescription ? true : false,
        variations_json: JSON.stringify(variations.value),
    })).post(`/owner/products/${props.product.id}`, {
        forceFormData: true,
    });
};
</script>
