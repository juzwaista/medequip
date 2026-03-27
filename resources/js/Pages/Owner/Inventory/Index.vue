<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
                    <p class="text-gray-600 mt-1">Manage your products and stock levels</p>
                </div>
                <Link 
                    href="/owner/inventory/create"
                    class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition font-bold shadow-lg flex items-center"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Product
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <input 
                            v-model="search"
                            @input="applyFilters"
                            type="text"
                            placeholder="Search by product name or SKU..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <div>
                        <select 
                            v-model="categoryFilter"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <select 
                            v-model="stockFilter"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Stock Levels</option>
                            <option value="out">Out of Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="in_stock">In Stock</option>
                        </select>
                    </div>

                    <!-- DSS Alert Filter -->
                    <div>
                        <select
                            v-model="alertFilter"
                            @change="applyFilters"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All DSS flags</option>
                            <option value="expired">Expired</option>
                            <option value="expiring">Near expiry (&lt; 30 days)</option>
                            <option value="low_stock">Low stock (reorder-level)</option>
                            <option value="predicted_stockout">Predicted stockout (≤5 days)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="alertFilter" class="bg-rose-50 border border-rose-200 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-rose-100 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-rose-900">
                            {{ alertFilter === 'expired'
                                ? 'Action Required: Expired Products'
                                : (alertFilter === 'expiring' || alertFilter === 'near_expiry')
                                    ? 'Action Required: Near-expiry Products (< 30 Days)'
                                    : (alertFilter === 'predicted_stockout')
                                        ? 'Action Required: Predicted Stockout (≤5 days)'
                                        : 'Action Required: Low Stock Products' }}
                        </p>
                        <p class="text-xs font-medium text-rose-700 mt-0.5">Showing only items flagged by the dashboard.</p>
                    </div>
                </div>
                <button 
                    @click="clearAlertFilter"
                    class="text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-100 bg-white px-4 py-2 rounded-lg border border-rose-200 transition-colors"
                >
                    Clear Filter & Show All
                </button>
            </div>

            <div v-if="products.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="product in products.data" 
                    :key="product.id" 
                    class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition"
                >
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative">
                        <img 
                            v-if="product.image_path" 
                            :src="`/storage/${product.image_path}`" 
                            :alt="product.name" 
                            class="w-full h-full object-cover" 
                        />
                        <svg v-else class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        
                        <div 
                            class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-bold shadow-lg flex items-center"
                            :class="{
                                'bg-red-100 text-red-800 border-2 border-red-300': product.stock_status === 'out' || product.stock_status === 'expired',
                                'bg-orange-100 text-orange-800 border-2 border-orange-300': product.stock_status === 'low' || product.stock_status === 'near_expiry',
                                'bg-yellow-100 text-yellow-800 border-2 border-yellow-300': product.stock_status === 'medium',
                                'bg-green-100 text-green-800 border-2 border-green-300': product.stock_status === 'good'
                            }"
                        >
                            <span 
                                class="inline-block w-2 h-2 rounded-full mr-1"
                                :class="{
                                    'bg-red-600': product.stock_status === 'out' || product.stock_status === 'expired',
                                    'bg-orange-600': product.stock_status === 'low' || product.stock_status === 'near_expiry',
                                    'bg-yellow-600': product.stock_status === 'medium',
                                    'bg-green-600': product.stock_status === 'good'
                                }"
                            ></span>
                            {{ product.stock_label }}
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-900 mb-1">{{ product.name }}</h3>
                                <p class="text-xs text-gray-500">SKU: {{ product.sku }}</p>
                            </div>
                            <span 
                                :class="[
                                    'px-2 py-1 rounded-full text-xs font-semibold ml-2',
                                    product.is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                                ]"
                            >
                                {{ product.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 mb-3">{{ product.category?.name || 'Uncategorized' }}</p>

                        <div class="bg-gray-50 rounded-lg p-3 mb-3 border border-gray-200">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Total Stock:</span>
                                <span class="text-2xl font-bold" :class="{
                                    'text-red-600': product.stock_status === 'out' || product.stock_status === 'expired',
                                    'text-orange-600': product.stock_status === 'low' || product.stock_status === 'near_expiry',
                                    'text-yellow-600': product.stock_status === 'medium',
                                    'text-green-600': product.stock_status === 'good'
                                }">
                                    {{ product.total_stock }}
                                </span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Available: {{ product.available_stock }}</span>
                                <span v-if="product.total_reserved > 0" class="text-orange-600">Reserved: {{ product.total_reserved }}</span>
                            </div>
                        </div>

                        <p class="text-xl font-bold text-blue-600 mb-4">₱{{ Number(product.base_price).toLocaleString() }}</p>

                        <div class="flex gap-2">
                            <Link 
                                :href="`/owner/inventory/${product.id}/edit`"
                                class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm"
                            >
                                <svg class="h-4 w-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </Link>
                            <button 
                                @click="openStockModal(product)"
                                class="flex-1 px-4 py-2 border-2 border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition font-medium text-sm"
                            >
                                <svg class="h-4 w-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Stock
                            </button>
                            <button 
                                @click="deleteProduct(product.id)"
                                class="px-4 py-2 border-2 border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition font-medium text-sm"
                            >
                                <svg class="h-4 w-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-md p-12 text-center mt-6">
                <svg class="h-24 w-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No Products Found</h3>
                <p v-if="alertFilter" class="text-gray-600 mb-4">No products match the selected dashboard alert criteria.</p>
                <p v-else class="text-gray-600 mb-4">Start by adding your first product to inventory</p>
                <button 
                    v-if="alertFilter"
                    @click="clearAlertFilter"
                    class="text-blue-600 hover:text-blue-700 font-bold mt-2 inline-block"
                >
                    Clear filters and view all products
                </button>
                <Link 
                    v-else
                    href="/owner/inventory/create"
                    class="text-blue-600 hover:text-blue-700 font-bold mt-2 inline-block"
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
                            'px-4 py-2 rounded-lg text-sm font-medium',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        :class="[
                            'px-4 py-2 rounded-lg bg-gray-50 text-gray-400 border border-gray-100 cursor-not-allowed text-sm font-medium'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showStockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] px-4" @click="closeStockModal">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Adjust Stock</h3>
                        <button @click="closeStockModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="selectedProduct" class="mb-4">
                        <p class="text-sm font-medium text-gray-600 mb-2">{{ selectedProduct.name }}</p>
                        <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                            <p class="text-sm text-gray-700">Current Stock: <span class="font-bold text-blue-600">{{ selectedProduct.total_stock }}</span></p>
                        </div>
                    </div>

                    <form @submit.prevent="submitStockAdjustment">
                        <div v-if="selectedProduct?.variations?.length" class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Product option *</label>
                            <select
                                v-model.number="stockVariationId"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                            >
                                <option
                                    v-for="v in (selectedProduct.variations || []).filter((x) => x.is_active !== false)"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.display_label || `${v.option_name}: ${v.option_value}` }}
                                </option>
                            </select>
                            <p class="text-xs font-medium text-gray-500 mt-1">Adjust stock for the selected variation.</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Adjustment</label>
                            <div class="flex gap-2 mb-3">
                                <button 
                                    type="button"
                                    @click="stockAdjustment = stockAdjustment - 1"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold"
                                >
                                    -
                                </button>
                                <input 
                                    v-model.number="stockAdjustment"
                                    type="number"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-center font-bold text-lg focus:ring-2 focus:ring-blue-500"
                                />
                                <button 
                                    type="button"
                                    @click="stockAdjustment = stockAdjustment + 1"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold"
                                >
                                    +
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="stockAdjustment = 10" class="flex-1 px-3 py-1.5 bg-gray-100 text-gray-700 font-medium rounded text-sm hover:bg-gray-200">+10</button>
                                <button type="button" @click="stockAdjustment = 50" class="flex-1 px-3 py-1.5 bg-gray-100 text-gray-700 font-medium rounded text-sm hover:bg-gray-200">+50</button>
                                <button type="button" @click="stockAdjustment = -10" class="flex-1 px-3 py-1.5 bg-gray-100 text-gray-700 font-medium rounded text-sm hover:bg-gray-200">-10</button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Reason (optional)</label>
                            <input 
                                v-model="stockReason"
                                type="text"
                                placeholder="e.g., New shipment arrived"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div class="flex gap-3">
                            <button 
                                type="button"
                                @click="closeStockModal"
                                class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-bold"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="adjustingStock"
                                class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-bold disabled:opacity-50"
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
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

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
const adjustingStock = ref(false);

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
    const vars = (product.variations || []).filter((x) => x.is_active !== false);
    stockVariationId.value = vars.length ? vars[0].id : null;
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
    };
    if (selectedProduct.value.variations?.length) {
        payload.product_variation_id = stockVariationId.value;
    }

    router.post(`/owner/inventory/${selectedProduct.value.id}/adjust`, payload, {
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