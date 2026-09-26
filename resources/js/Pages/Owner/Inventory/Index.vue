<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-6 pb-28 sm:pb-10 min-w-0">
            <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold text-ink">Inventory</h1>
                    <p class="text-ink-soft mt-1 text-sm sm:text-base">Products and stock levels</p>
                </div>
                <Link 
                    href="/owner/inventory/create"
                    class="bg-brand text-white px-5 py-3.5 rounded-card hover:bg-brand-dark transition font-bold shadow-lg flex items-center justify-center gap-2 w-full sm:w-auto min-h-[48px] touch-manipulation shrink-0"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Product
                </Link>
            </div>

            <div class="bg-white rounded-card shadow-md p-4 sm:p-6 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="sm:col-span-2 lg:col-span-2 min-w-0">
                        <input 
                            v-model="search"
                            @input="applyFilters"
                            type="text"
                            placeholder="Search by product name or SKU..."
                            class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent min-h-[48px] text-base sm:text-sm touch-manipulation"
                        />
                    </div>

                    <div class="min-w-0">
                        <select 
                            v-model="categoryFilter"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent min-h-[48px] text-base sm:text-sm touch-manipulation"
                        >
                            <option value="">All Categories</option>
                            <template v-for="parent in categories" :key="parent.id">
                                <optgroup :label="parent.name">
                                    <option :value="parent.id">All in {{ parent.name }}</option>
                                    <option
                                        v-for="ch in (parent.children || [])"
                                        :key="ch.id"
                                        :value="ch.id"
                                    >
                                        {{ ch.name }}
                                    </option>
                                </optgroup>
                            </template>
                        </select>
                    </div>

                    <div class="min-w-0">
                        <select 
                            v-model="stockFilter"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent min-h-[48px] text-base sm:text-sm touch-manipulation"
                        >
                            <option value="">All Stock Levels</option>
                            <option value="out">Out of Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="in_stock">In Stock</option>
                        </select>
                    </div>

                    <!-- DSS Alert Filter -->
                    <div class="min-w-0 sm:col-span-2 lg:col-span-4">
                        <select
                            v-model="alertFilter"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent min-h-[48px] text-base sm:text-sm touch-manipulation"
                        >
                            <option value="">All DSS flags</option>
                            <option value="expired">Expired</option>
                            <option value="expiring">Near expiry (within {{ expiryWarningDays }} days)</option>
                            <option value="low_stock">Low stock (reorder-level)</option>
                            <option value="predicted_stockout">Predicted stockout (≤5 days)</option>
                        </select>
                    </div>
                </div>
            </div>


            <div v-if="products.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
                <div 
                    v-for="product in products.data" 
                    :key="product.id" 
                    class="bg-white rounded-card shadow-md overflow-hidden hover:shadow-xl transition flex flex-col"
                >
                    <div class="w-full aspect-square bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative shrink-0 overflow-hidden">
                        <img 
                            v-if="product.image_path" 
                            :src="`/storage/${product.image_path}`" 
                            :alt="product.name" 
                            class="w-full h-full object-cover" 
                        />
                        <svg v-else class="h-12 w-12 sm:h-16 sm:w-16 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        
                        <div 
                            class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs sm:text-xs font-bold shadow-lg flex items-center"
                            :class="{
                                'bg-red-100 text-red-800 border-2 border-red-300': product.stock_status === 'out' || product.stock_status === 'expired',
                                'bg-orange-100 text-orange-800 border-2 border-orange-300': product.stock_status === 'low' || product.stock_status === 'near_expiry',
                                'bg-yellow-100 text-yellow-800 border-2 border-yellow-300': product.stock_status === 'medium',
                                'bg-brand-tint text-brand-dark border-2 border-brand-soft': product.stock_status === 'good'
                            }"
                        >
                            <span 
                                class="inline-block w-1.5 h-1.5 rounded-full mr-1 hidden sm:inline-block"
                                :class="{
                                    'bg-red-600': product.stock_status === 'out' || product.stock_status === 'expired',
                                    'bg-orange-600': product.stock_status === 'low' || product.stock_status === 'near_expiry',
                                    'bg-yellow-600': product.stock_status === 'medium',
                                    'bg-brand': product.stock_status === 'good'
                                }"
                            ></span>
                            {{ product.stock_label }}
                        </div>
                    </div>

                    <div class="p-3 sm:p-4 flex flex-col flex-1 h-full">
                        <div class="mb-1.5 min-h-[36px] sm:min-h-[44px]">
                            <h3 class="font-bold text-sm sm:text-base text-ink mb-0.5 truncate">{{ product.name }}</h3>
                            <p class="text-xs sm:text-xs text-ink-soft truncate">SKU: {{ product.sku }}</p>
                        </div>

                        <p class="text-[11px] sm:text-xs text-ink-soft mb-2 truncate min-h-[16px]">{{ product.category?.name || 'Uncategorized' }}</p>

                        <div class="bg-mist rounded-control p-2 mb-3 border border-line min-h-[56px] flex flex-col justify-center">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[11px] sm:text-xs font-semibold text-ink">Stock:</span>
                                <span class="text-base sm:text-lg font-bold" :class="{
                                    'text-red-600': product.stock_status === 'out' || product.stock_status === 'expired',
                                    'text-orange-600': product.stock_status === 'low' || product.stock_status === 'near_expiry',
                                    'text-yellow-600': product.stock_status === 'medium',
                                    'text-brand': product.stock_status === 'good'
                                }">
                                    {{ product.has_mixed_packs ? product.stock_pieces : product.total_stock }}<span v-if="product.has_mixed_packs" class="text-xs font-semibold text-ink-soft ml-0.5">pcs</span>
                                </span>
                            </div>
                            <div class="flex justify-between text-xs sm:text-[11px] text-ink-soft leading-tight">
                                <span>Avail: {{ product.has_mixed_packs ? `${product.available_pieces} pcs` : product.available_stock }}</span>
                                <span class="text-orange-600 h-3 sm:h-4 w-12 text-right">
                                    <template v-if="product.total_reserved > 0">Rsrv: {{ product.total_reserved }}</template>
                                </span>
                            </div>
                        </div>

                         <div class="mt-auto">
                            <!-- Price: show range if variations exist, else base price -->
                            <div class="mb-2">
                                <template v-if="product.variations && product.variations.length > 0">
                                    <template v-if="priceRange(product).min === priceRange(product).max">
                                        <p class="text-base sm:text-lg font-bold text-brand">₱{{ Number(priceRange(product).min).toLocaleString() }}</p>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm sm:text-base font-bold text-brand leading-tight">
                                            ₱{{ Number(priceRange(product).min).toLocaleString() }}
                                            <span class="text-ink-faint font-normal">–</span>
                                            ₱{{ Number(priceRange(product).max).toLocaleString() }}
                                        </p>
                                        <p class="text-xs text-ink-faint mt-0.5">{{ product.variations.length }} variation{{ product.variations.length === 1 ? '' : 's' }}</p>
                                    </template>
                                </template>
                                <template v-else>
                                    <p class="text-base sm:text-lg font-bold text-brand">₱{{ Number(product.base_price).toLocaleString() }}</p>
                                </template>
                            </div>

                            <div class="flex gap-1.5">
                                <Link 
                                    :href="`/owner/inventory/${product.id}/edit`"
                                    class="flex-1 min-w-0 text-center px-1.5 py-1.5 bg-brand text-white rounded-control hover:bg-brand-dark transition font-medium text-[11px] sm:text-xs flex items-center justify-center touch-manipulation"
                                >
                                    Edit
                                </Link>
                                <button 
                                    type="button"
                                    @click="openStockModal(product)"
                                    class="flex-1 min-w-0 px-1.5 py-1.5 border-2 border-brand text-brand rounded-control hover:bg-brand-tint transition font-medium text-[11px] sm:text-xs flex items-center justify-center touch-manipulation"
                                >
                                    Manage
                                </button>
                                <button 
                                    type="button"
                                    title="Archive product"
                                    @click="deleteProduct(product.id)"
                                    class="w-8 h-8 px-0 border-2 border-red-600 text-red-600 rounded-control hover:bg-red-50 transition font-medium inline-flex items-center justify-center touch-manipulation shrink-0"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-card shadow-md p-12 text-center mt-6">
                <svg class="h-24 w-24 text-ink-faint mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-bold text-ink mb-2">No Products Found</h3>
                <p v-if="alertFilter" class="text-ink-soft mb-4">No products match the selected dashboard alert criteria.</p>
                <p v-else class="text-ink-soft mb-4">Start by adding your first product to inventory</p>
                <button 
                    v-if="alertFilter"
                    @click="clearAlertFilter"
                    class="text-brand hover:text-brand-dark font-bold mt-2 inline-block"
                >
                    Clear filters and view all products
                </button>
                <Link 
                    v-else
                    href="/owner/inventory/create"
                    class="text-brand hover:text-brand-dark font-bold mt-2 inline-block"
                >
                    Add your first product
                </Link>
            </div>

            <div v-if="products.data.length > 0" class="mt-6 flex justify-center gap-2 flex-wrap">
                <template v-for="link in products.links" :key="link.label">
                    <Link 
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'px-4 py-2 rounded-control text-sm font-medium',
                            link.active ? 'bg-brand text-white' : 'bg-white text-ink hover:bg-mist border border-line'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        :class="[
                            'px-4 py-2 rounded-control bg-mist text-ink-faint border border-line cursor-not-allowed text-sm font-medium'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showStockModal" class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-[100] sm:p-4" @click="closeStockModal">
                <div class="bg-white rounded-t-card sm:rounded-card shadow-2xl max-w-md w-full p-5 sm:p-6 max-h-[90dvh] overflow-y-auto overscroll-contain" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-ink">Manage Stock & Pricing</h3>
                        <button @click="closeStockModal" class="text-ink-faint hover:text-ink-soft">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="selectedProduct" class="mb-4">
                        <p class="text-sm font-medium text-ink-soft mb-2">{{ selectedProduct.name }}</p>
                        <div class="bg-brand-tint rounded-control p-3 border border-brand-soft">
                            <p class="text-sm text-ink">Current Stock: <span class="font-bold text-brand">{{ selectedProduct.total_stock }}</span></p>
                        </div>
                    </div>

                    <form @submit.prevent="submitStockAdjustment">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-ink mb-2">Base Price (₱) *</label>
                            <input 
                                v-model.number="stockBasePrice"
                                type="number"
                                step="0.01"
                                required
                                class="w-full px-4 py-2 border border-line rounded-control focus:ring-2 focus:ring-brand"
                            />
                        </div>

                        <div v-if="selectedProduct?.variations?.length" class="mb-4">
                            <label class="block text-sm font-bold text-ink mb-2">Product option *</label>
                            <select
                                v-model.number="stockVariationId"
                                required
                                class="w-full px-4 py-2 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"
                            >
                                <option
                                    v-for="v in (selectedProduct.variations || []).filter((x) => x.is_active !== false)"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.display_label || `${v.option_name}: ${v.option_value}` }}
                                </option>
                            </select>
                            <p class="text-xs font-medium text-ink-soft mt-1">Select variation to adjust stock and reorder level.</p>
                        </div>

                        <div class="mb-4 flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-ink mb-2">Reorder Level *</label>
                                <input 
                                    v-model.number="stockReorderLevel"
                                    type="number"
                                    min="0"
                                    required
                                    class="w-full px-4 py-2 border border-line rounded-control text-center font-bold text-lg focus:ring-2 focus:ring-brand"
                                />
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-ink mb-2">Stock Adj.</label>
                                <div class="flex gap-1">
                                    <button 
                                        type="button"
                                        @click="stockAdjustment = stockAdjustment - 1"
                                        class="px-2 py-2 bg-red-600 text-white rounded-control hover:bg-red-700 font-bold"
                                    >
                                        -
                                    </button>
                                    <input 
                                        v-model.number="stockAdjustment"
                                        type="number"
                                        class="w-full px-2 py-2 border border-line rounded-control text-center font-bold text-lg focus:ring-2 focus:ring-brand"
                                    />
                                    <button 
                                        type="button"
                                        @click="stockAdjustment = stockAdjustment + 1"
                                        class="px-2 py-2 bg-brand text-white rounded-control hover:bg-brand-dark font-bold"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-bold text-ink mb-2">Adjustment</label>
                            <div class="flex gap-2 mb-3">
                                <button 
                                    type="button"
                                    @click="stockAdjustment = stockAdjustment - 1"
                                    class="px-4 py-2 bg-red-600 text-white rounded-control hover:bg-red-700 font-bold"
                                >
                                    -
                                </button>
                                <input 
                                    v-model.number="stockAdjustment"
                                    type="number"
                                    class="flex-1 px-4 py-2 border border-line rounded-control text-center font-bold text-lg focus:ring-2 focus:ring-brand"
                                />
                                <button 
                                    type="button"
                                    @click="stockAdjustment = stockAdjustment + 1"
                                    class="px-4 py-2 bg-brand text-white rounded-control hover:bg-brand-dark font-bold"
                                >
                                    +
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="stockAdjustment += 10" class="flex-1 px-3 py-1.5 bg-mist text-ink font-medium rounded text-sm hover:bg-line">+10</button>
                                <button type="button" @click="stockAdjustment += 50" class="flex-1 px-3 py-1.5 bg-mist text-ink font-medium rounded text-sm hover:bg-line">+50</button>
                                <button type="button" @click="stockAdjustment -= 10" class="flex-1 px-3 py-1.5 bg-mist text-ink font-medium rounded text-sm hover:bg-line">-10</button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-ink mb-2">Reason (optional)</label>
                            <input 
                                v-model="stockReason"
                                type="text"
                                placeholder="e.g., New shipment arrived"
                                class="w-full px-4 py-2 border border-line rounded-control focus:ring-2 focus:ring-brand"
                            />
                        </div>

                        <div class="flex gap-3">
                            <button 
                                type="button"
                                @click="closeStockModal"
                                class="flex-1 px-4 py-3 border border-line text-ink rounded-control hover:bg-mist transition font-bold"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="adjustingStock"
                                class="flex-1 px-4 py-3 bg-brand text-white rounded-control hover:bg-brand-dark transition font-bold disabled:opacity-50"
                            >
                                {{ adjustingStock ? 'Adjusting...' : 'Confirm' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </OwnerLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    expiry_warning_days: { type: Number, default: 60 },
});

const expiryWarningDays = computed(() => {
    const n = Number(props.expiry_warning_days);
    return Number.isFinite(n) && n >= 1 ? Math.min(365, Math.round(n)) : 60;
});

// Returns the min and max price across a product's variations
const priceRange = (product) => {
    const vars = product.variations || [];
    if (!vars.length) return { min: product.base_price, max: product.base_price };
    const prices = vars.map(v => Number(v.price ?? product.base_price)).filter(p => !isNaN(p));
    if (!prices.length) return { min: product.base_price, max: product.base_price };
    return { min: Math.min(...prices), max: Math.max(...prices) };
};

// NEW: Catch the dashboard filter
const alertFilter = ref(props.filters.filter || '');
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || '');
const stockFilter = ref(props.filters.stock_status || '');

watch(
    () => props.filters,
    (f) => {
        if (!f) {
            return;
        }
        alertFilter.value = f.filter || '';
        search.value = f.search || '';
        categoryFilter.value = f.category_id || '';
        stockFilter.value = f.stock_status || '';
    },
    { deep: true }
);

const showStockModal = ref(false);
const selectedProduct = ref(null);
const stockVariationId = ref(null);
const stockAdjustment = ref(0);
const stockReason = ref('');
const stockBasePrice = ref(0);
const stockReorderLevel = ref(10);
const adjustingStock = ref(false);

const updateReorderLevel = (varId) => {
    if (!selectedProduct.value || !selectedProduct.value.inventory) {
        stockReorderLevel.value = 10;
        return;
    }
    const inv = selectedProduct.value.inventory.find(i => i.product_variation_id === varId);
    stockReorderLevel.value = inv ? inv.reorder_level : 10;
};

watch(stockVariationId, (newId) => {
    if (showStockModal.value) {
        updateReorderLevel(newId);
    }
});

const applyFilters = () => {
    router.get('/owner/inventory', {
        search: search.value,
        category_id: categoryFilter.value,
        stock_status: stockFilter.value,
        filter: alertFilter.value, // Include the alert filter if active
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// NEW: Function to clear just the dashboard alert filter
const clearAlertFilter = () => {
    alertFilter.value = '';
    applyFilters();
};

const openStockModal = (product) => {
    selectedProduct.value = product;
    stockAdjustment.value = 0;
    stockReason.value = '';
    stockBasePrice.value = Number(product.base_price) || 0;
    const vars = (product.variations || []).filter((x) => x.is_active !== false);
    stockVariationId.value = vars.length ? vars[0].id : null;
    updateReorderLevel(stockVariationId.value);
    showStockModal.value = true;
};

const closeStockModal = () => {
    showStockModal.value = false;
    selectedProduct.value = null;
    stockAdjustment.value = 0;
    stockReason.value = '';
    stockVariationId.value = null;
};

const submitStockAdjustment = () => {
    adjustingStock.value = true;

    const payload = {
        adjustment: stockAdjustment.value,
        reason: stockReason.value,
        base_price: stockBasePrice.value,
        reorder_level: stockReorderLevel.value,
    };
    if (selectedProduct.value.variations?.length) {
        payload.product_variation_id = stockVariationId.value;
    }

    router.post(`/owner/inventory/${selectedProduct.value.id}/quick-edit`, payload, {
        onSuccess: () => { closeStockModal(); },
        onFinish: () => { adjustingStock.value = false; }
    });
};

const deleteProduct = (id) => {
    if (confirm('Archive this product? It will be hidden from the shop but kept in the database.')) {
        router.delete(`/owner/inventory/${id}`);
    }
};
</script>