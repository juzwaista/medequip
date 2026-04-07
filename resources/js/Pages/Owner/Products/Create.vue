<template>
    <OwnerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Add product</h1>
                <p class="text-gray-600 mt-2">Fill in the details below to list a new product in your shop.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Media -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Product images</h2>
                    <p class="text-sm text-gray-600 mb-4">
                        Add up to <strong>12</strong> images. The <strong>first</strong> image is used as the main photo in listings.
                    </p>
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        @change="onImagesPick"
                        class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p v-if="form.errors.images" class="text-red-600 text-sm mt-2">{{ form.errors.images }}</p>
                    <div v-if="imagePreviews.length" class="mt-4 flex flex-wrap gap-3">
                        <div
                            v-for="(p, idx) in imagePreviews"
                            :key="p.key"
                            class="relative w-24 h-24 rounded-xl border border-gray-200 overflow-hidden group"
                        >
                            <img :src="p.url" alt="" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                @click="removeImage(idx)"
                                class="absolute inset-0 bg-black/50 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition flex items-center justify-center"
                            >
                                Remove
                            </button>
                            <span
                                v-if="idx === 0"
                                class="absolute bottom-1 left-1 right-1 text-center text-[10px] font-bold bg-blue-600 text-white rounded px-1 py-0.5"
                            >
                                Main
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Basics -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Basic information</h2>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Product name <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Category <span class="text-red-500">*</span></label>
                            <select
                                v-model="form.category_id"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                            >
                                <option value="">Select a category</option>
                                <template v-for="group in categoryGroups" :key="group.parent.id">
                                    <optgroup :label="group.parent.name">
                                        <option :value="group.parent.id">{{ group.parent.name }} (general)</option>
                                        <option v-for="c in group.children" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </optgroup>
                                </template>
                            </select>
                            <p v-if="form.errors.category_id" class="text-red-600 text-sm mt-1">{{ form.errors.category_id }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Brand <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.brand"
                                    type="text"
                                    required
                                    placeholder="e.g. Omron"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500"
                                />
                                <p v-if="form.errors.brand" class="text-red-600 text-sm mt-1">{{ form.errors.brand }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Model <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.model"
                                    type="text"
                                    required
                                    placeholder="e.g. HEM-7120"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500"
                                />
                                <p v-if="form.errors.model" class="text-red-600 text-sm mt-1">{{ form.errors.model }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Medicine -->
                <section
                    v-if="isMedicineCategory"
                    class="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-sm p-6 sm:p-8"
                >
                    <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-800 mb-2">Medicine compliance</h2>
                    <p class="text-sm text-indigo-900/80 mb-4">
                        For prescription-only items, customers will upload a prescription photo after checkout. You approve or reject before they can pay.
                    </p>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            v-model="form.requires_prescription"
                            type="checkbox"
                            class="mt-1 h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm font-medium text-gray-900">Requires a valid prescription to purchase</span>
                    </label>
                </section>

                <!-- Description -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Description</h2>
                    <label class="block text-sm font-semibold text-gray-800 mb-1.5">Details <span class="text-red-500">*</span></label>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500"
                        placeholder="Specifications, inclusions, usage…"
                    />
                    <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
                </section>

                <!-- Pricing -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Retail price (₱) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                <input
                                    v-model="form.base_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="w-full pl-9 pr-4 py-3 border border-gray-300 rounded-xl"
                                />
                            </div>
                            <p v-if="form.errors.base_price" class="text-red-600 text-sm mt-1">{{ form.errors.base_price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Wholesale price (₱)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                <input
                                    v-model="form.wholesale_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full pl-9 pr-4 py-3 border border-gray-300 rounded-xl"
                                />
                            </div>
                        </div>
                        <div v-if="form.wholesale_price" class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Minimum wholesale quantity</label>
                            <input
                                v-model="form.wholesale_min_qty"
                                type="number"
                                min="1"
                                class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-xl"
                            />
                        </div>
                    </div>
                </section>

                <!-- Variations -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Variations</h2>
                            <p class="text-sm text-gray-600 mt-1">Optional — e.g. color, size. Price adjustment adds to retail / wholesale.</p>
                        </div>
                        <button
                            type="button"
                            @click="addVariation"
                            class="text-sm font-semibold text-blue-600 hover:text-blue-800 px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50"
                        >
                            + Add option
                        </button>
                    </div>
                    <p v-if="!variations.length" class="text-sm text-gray-500 italic">No variations — sold as a single option.</p>
                    <div v-for="(row, idx) in variations" :key="idx" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4 p-4 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="md:col-span-3">
                            <label class="text-xs font-medium text-gray-600">Option name</label>
                            <input v-model="row.option_name" placeholder="Color" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-xs font-medium text-gray-600">Value</label>
                            <input v-model="row.option_value" placeholder="Blue" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-gray-600">Price ± ₱</label>
                            <input v-model.number="row.price_adjustment" type="number" step="0.01" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-xs font-medium text-gray-600">Variation SKU (optional)</label>
                            <input v-model="row.sku" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm font-mono" />
                        </div>
                        <div class="md:col-span-1 flex items-end justify-end">
                            <button type="button" @click="removeVariation(idx)" class="text-red-600 p-2 hover:bg-red-50 rounded-lg" title="Remove">✕</button>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 bg-blue-600 text-white px-6 py-3.5 rounded-xl font-bold hover:bg-blue-700 shadow-md disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Creating…' : 'Create product' }}
                    </button>
                    <Link
                        href="/owner/products"
                        class="px-6 py-3.5 border-2 border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 text-center"
                    >
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
    categories: { type: Array, default: () => [] },
    medicine_category_ids: { type: Array, default: () => [] },
});

const variations = ref([]);

const form = useForm({
    name: '',
    category_id: '',
    brand: '',
    model: '',
    description: '',
    base_price: '',
    wholesale_price: '',
    wholesale_min_qty: '',
    requires_prescription: false,
    images: [],
});

const imagePreviews = ref([]);

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

const onImagesPick = (e) => {
    const picked = Array.from(e.target.files || []);
    const merged = [...(form.images || []), ...picked].slice(0, 12);
    form.images = merged;
    imagePreviews.value = merged.map((file, i) => ({
        key: `${file.name}-${i}-${file.size}`,
        url: URL.createObjectURL(file),
    }));
    e.target.value = '';
};

const removeImage = (idx) => {
    const next = [...form.images];
    next.splice(idx, 1);
    form.images = next;
    imagePreviews.value = next.map((file, i) => ({
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
    });
};

const removeVariation = (idx) => {
    variations.value.splice(idx, 1);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        requires_prescription: data.requires_prescription ? true : false,
        variations_json: JSON.stringify(variations.value),
    })).post('/owner/products', {
        forceFormData: true,
    });
};
</script>
