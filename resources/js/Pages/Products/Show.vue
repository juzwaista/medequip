<template>
    <MainLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Breadcrumb -->
            <nav class="flex flex-wrap items-center gap-1.5 mb-6 text-xs sm:text-sm text-gray-600">
                <Link href="/products" class="hover:text-blue-600 transition">Products</Link>
                <span class="text-gray-400">/</span>
                <Link :href="`/category/${product.category.id}`" class="hover:text-blue-600 transition truncate max-w-[10rem] sm:max-w-none">
                    {{ product.category.name }}
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium truncate">{{ product.name }}</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
                <!-- Gallery -->
                <div class="lg:col-span-6 space-y-3">
                    <div class="aspect-square rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                        <img
                            v-if="activeImageUrl"
                            :src="activeImageUrl"
                            :alt="product.name"
                            class="w-full h-full object-contain"
                        />
                        <svg v-else class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div v-if="galleryUrls.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="(url, idx) in galleryUrls"
                            :key="idx"
                            type="button"
                            @click="activeImageIndex = idx"
                            class="flex-shrink-0 w-16 h-16 rounded-lg border-2 overflow-hidden transition"
                            :class="activeImageIndex === idx ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <img :src="url" alt="" class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- Buy box -->
                <div class="lg:col-span-6">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span
                            v-if="product.requires_prescription"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-900 border border-amber-200"
                        >
                            Prescription required
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-800 border border-blue-100">
                            {{ product.product_type === 'equipment' ? 'Equipment' : 'Consumable' }}
                        </span>
                        <span v-if="product.has_warranty" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">
                            {{ product.warranty_months }} mo warranty
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">{{ product.name }}</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ product.brand }} · {{ product.model }}
                    </p>

                    <!-- Price block (compact) -->
                    <div class="mt-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-2xl font-bold text-gray-900">₱{{ Number(effectiveRetail).toLocaleString() }}</span>
                            <span class="text-sm text-gray-500">Retail</span>
                        </div>
                        <div v-if="product.wholesale_price" class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold text-emerald-700">₱{{ Number(effectiveWholesale).toLocaleString() }}</span>
                                <span class="text-gray-600">wholesale</span>
                                <span class="text-xs text-gray-500">· min {{ product.wholesale_min_qty }} pcs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Variations -->
                    <div v-if="hasVariations" class="mt-5">
                        <p class="text-sm font-medium text-gray-800 mb-2">{{ variationOptionLabel }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="v in variationStocks"
                                :key="v.id"
                                type="button"
                                @click="selectedVariationId = v.id"
                                :disabled="v.available <= 0"
                                class="px-3 py-1.5 rounded-lg border text-sm font-medium transition"
                                :class="selectedVariationId === v.id
                                    ? 'border-blue-600 bg-blue-50 text-blue-900'
                                    : v.available <= 0
                                        ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-50'
                                        : 'border-gray-300 text-gray-800 hover:border-blue-400'"
                            >
                                {{ v.option_value }}
                                <span class="text-xs font-normal text-gray-500">({{ v.available }})</span>
                            </button>
                        </div>
                        <p v-if="!selectedVariationId" class="text-xs text-amber-700 mt-2">Select an option to add to cart.</p>
                    </div>

                    <!-- Stock -->
                    <p class="mt-4 text-sm">
                        <span class="font-medium text-gray-800">{{ hasVariations ? 'Total Stock:' : 'Stock:' }}</span>
                        <span :class="totalStock > 0 ? 'text-emerald-700' : 'text-red-600'" class="ml-1 font-semibold">
                            {{ totalStock > 0 ? `${totalStock} available` : 'Out of stock' }}
                        </span>
                    </p>

                    <!-- Seller -->
                    <div class="mt-5 rounded-lg bg-gray-50 border border-gray-100 px-3 py-2.5 text-sm">
                        <span class="text-gray-500">Sold by </span>
                        <Link
                            v-if="product.distributor.slug"
                            :href="`/seller/${product.distributor.slug}`"
                            class="font-semibold text-blue-600 hover:underline"
                        >
                            {{ product.distributor.company_name }}
                        </Link>
                        <span v-else class="font-semibold text-gray-900">{{ product.distributor.company_name }}</span>
                    </div>

                    <!-- Quantity + CTA -->
                    <div class="mt-6 space-y-3">
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Qty</label>
                            <div class="inline-flex items-center border-2 border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                                <button
                                    type="button"
                                    @click="bumpQty(-1)"
                                    :disabled="quantity <= 1 || lineAvailable <= 0"
                                    class="w-11 h-11 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-40 text-lg font-bold"
                                >−</button>
                                <input
                                    type="number"
                                    v-model.number="quantity"
                                    @input="sanitizeQuantityInput"
                                    min="1"
                                    :max="lineAvailable"
                                    class="w-14 text-center text-sm font-bold py-2 border-x-2 border-gray-200 focus:outline-none focus:ring-0"
                                />
                                <button
                                    type="button"
                                    @click="bumpQty(1)"
                                    :disabled="quantity >= lineAvailable || lineAvailable <= 0"
                                    class="w-11 h-11 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-40 text-lg font-bold"
                                >+</button>
                            </div>
                            <span class="text-xs text-gray-400">of {{ lineAvailable }} available</span>
                        </div>

                        <!-- Desktop CTA row -->
                        <div class="hidden sm:flex gap-3">
                            <button
                                type="button"
                                @click="addToCart"
                                :disabled="adding || lineAvailable <= 0 || (hasVariations && !selectedVariationId)"
                                class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl border-2 border-blue-600 text-blue-600 text-sm font-bold py-3 px-4 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                <svg v-if="adding" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                {{ adding ? 'Adding…' : 'Add to Cart' }}
                            </button>
                            <button
                                type="button"
                                @click="buyNow"
                                :disabled="buyingNow || lineAvailable <= 0 || (hasVariations && !selectedVariationId)"
                                class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 px-4 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-95"
                            >
                                <svg v-if="buyingNow" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Buy Now
                            </button>
                        </div>

                        <!-- Mobile note -->
                        <p class="sm:hidden text-xs text-gray-400 text-center">Use the buttons below to add to cart or buy now</p>
                    </div>
                </div>
            </div>

            <!-- Sticky Mobile Action Bar -->
            <div class="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-2xl px-4 py-3 z-40" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
                <div class="flex gap-3 items-center">
                    <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden flex-shrink-0">
                        <button type="button" @click="bumpQty(-1)" :disabled="quantity <= 1 || lineAvailable <= 0" class="w-10 h-10 flex items-center justify-center text-gray-600 disabled:opacity-40 text-base font-bold">−</button>
                        <span class="w-8 text-center text-sm font-bold">{{ quantity }}</span>
                        <button type="button" @click="bumpQty(1)" :disabled="quantity >= lineAvailable || lineAvailable <= 0" class="w-10 h-10 flex items-center justify-center text-gray-600 disabled:opacity-40 text-base font-bold">+</button>
                    </div>
                    <button
                        type="button"
                        @click="addToCart"
                        :disabled="adding || lineAvailable <= 0 || (hasVariations && !selectedVariationId)"
                        class="flex-1 flex items-center justify-center gap-1.5 h-11 rounded-xl border-2 border-blue-600 text-blue-600 font-bold text-sm hover:bg-blue-50 disabled:opacity-50 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        {{ adding ? 'Adding…' : 'Cart' }}
                    </button>
                    <button
                        type="button"
                        @click="buyNow"
                        :disabled="buyingNow || lineAvailable <= 0 || (hasVariations && !selectedVariationId)"
                        class="flex-1 flex items-center justify-center gap-1.5 h-11 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 disabled:opacity-50 transition shadow-sm"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Buy Now
                    </button>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-10 rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3">About this item</h2>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ product.description }}</p>

                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 text-sm">
                    <div class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Brand</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">{{ product.brand }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Model</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">{{ product.model }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Category</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">{{ product.category.name }}</dd>
                    </div>
                    <div v-if="product.has_expiry" class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Expiry</dt>
                        <dd class="font-semibold text-orange-700 mt-0.5">
                            {{ nearestExpiryDate ? new Date(nearestExpiryDate).toLocaleDateString() : 'Tracked per batch' }}
                        </dd>
                        <dd v-if="nearestBatchNumber" class="text-xs text-gray-500 mt-1">Batch {{ nearestBatchNumber }}</dd>
                    </div>
                    <div v-if="product.has_warranty" class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Warranty</dt>
                        <dd class="font-semibold text-emerald-700 mt-0.5">{{ product.warranty_months }} months</dd>
                    </div>
                </dl>
            </div>

            <!-- Related -->
            <div v-if="relatedProducts.length" class="mt-10">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Related products</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        v-for="related in relatedProducts"
                        :key="related.id"
                        :href="`/products/${related.id}`"
                        class="group rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-md transition"
                    >
                        <div class="aspect-square bg-gray-50 flex items-center justify-center">
                            <img
                                v-if="related.image_url"
                                :src="related.image_url"
                                :alt="related.name"
                                class="w-full h-full object-contain p-2"
                            />
                            <svg v-else class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-blue-600">{{ related.name }}</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">₱{{ Number(related.base_price).toLocaleString() }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    product: Object,
    relatedProducts: Array,
    totalStock: Number,
    availableStock: Number,
    hasVariations: {
        type: Boolean,
        default: false,
    },
    variationStocks: {
        type: Array,
        default: () => [],
    },
    nearestExpiryDate: {
        type: String,
        default: null,
    },
    nearestBatchNumber: {
        type: String,
        default: null,
    },
});

const quantity = ref(1);
const adding = ref(false);
const buyingNow = ref(false);
const activeImageIndex = ref(0);
const selectedVariationId = ref(null);

const galleryUrls = computed(() => {
    const imgs = props.product.images || [];
    if (imgs.length) {
        return imgs.map((i) => i.url || `/storage/${i.image_path}`);
    }
    if (props.product.image_url) {
        return [props.product.image_url];
    }
    return [];
});

const activeImageUrl = computed(() => galleryUrls.value[activeImageIndex.value] || null);

const selectedVariation = computed(() => {
    if (!props.hasVariations || !selectedVariationId.value) return null;
    return props.variationStocks.find((v) => v.id === selectedVariationId.value) || null;
});

const priceAdjustment = computed(() => (selectedVariation.value ? Number(selectedVariation.value.price_adjustment) : 0));

const effectiveRetail = computed(() => Number(props.product.base_price) + priceAdjustment.value);
const effectiveWholesale = computed(() =>
    props.product.wholesale_price ? Number(props.product.wholesale_price) + priceAdjustment.value : null
);

const variationOptionLabel = computed(() => {
    if (!props.variationStocks.length) return 'Options';
    return props.variationStocks[0].option_name || 'Option';
});

const lineAvailable = computed(() => {
    if (props.hasVariations) {
        return selectedVariation.value ? selectedVariation.value.available : 0;
    }
    return props.availableStock;
});

watch(
    () => selectedVariationId.value,
    () => {
        quantity.value = 1;
        sanitizeQuantityInput();
    }
);

watch(
    () => props.variationStocks,
    (rows) => {
        if (!props.hasVariations) return;
        const first = rows.find((r) => r.available > 0);
        selectedVariationId.value = first ? first.id : null;
    },
    { immediate: true }
);

const sanitizeQuantityInput = () => {
    const parsed = Number(quantity.value);
    if (!Number.isFinite(parsed) || parsed < 1) {
        quantity.value = 1;
        return;
    }
    if (lineAvailable.value && parsed > lineAvailable.value) {
        quantity.value = lineAvailable.value;
    }
};

const bumpQty = (delta) => {
    quantity.value = Math.max(1, Math.min(lineAvailable.value || 1, quantity.value + delta));
};

const addToCart = () => {
    sanitizeQuantityInput();
    if (props.hasVariations && !selectedVariationId.value) return;
    if (lineAvailable.value <= 0) return;

    adding.value = true;
    const payload = {
        product_id: props.product.id,
        quantity: quantity.value,
    };
    if (props.hasVariations && selectedVariationId.value) {
        payload.product_variation_id = selectedVariationId.value;
    }

    router.post('/cart/add', payload, {
        preserveScroll: true,
        onFinish: () => {
            adding.value = false;
        },
    });
};

const buyNow = async () => {
    sanitizeQuantityInput();
    if (props.hasVariations && !selectedVariationId.value) return;
    if (lineAvailable.value <= 0) return;

    buyingNow.value = true;
    try {
        const payload = { product_id: props.product.id, quantity: quantity.value };
        if (props.hasVariations && selectedVariationId.value) {
            payload.product_variation_id = selectedVariationId.value;
        }
        await window.axios.post('/cart/add', payload);
        router.visit('/checkout');
    } catch (e) {
        alert(e?.response?.data?.message || 'Could not process. Please try again.');
        buyingNow.value = false;
    }
};
</script>
