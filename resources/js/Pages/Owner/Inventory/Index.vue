<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
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

            <!-- Success Message -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-sm text-green-800 font-medium">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <input 
                            v-model="search"
                            @input="applyFilters"
                            type="text"
                            placeholder="Search by product name or SKU..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Category Filter -->
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

                    <!-- Stock Status Filter -->
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
                </div>
            </div>

            <!-- Products Grid -->
            <div v-if="products.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="product in products.data" 
                    :key="product.id" 
                    class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition"
                >
                    <!-- Product Image -->
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
                        
                        <!-- Stock Status Badge -->
                        <div 
                            class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-bold shadow-lg flex items-center"
                            :class="{
                                'bg-red-100 text-red-800 border-2 border-red-300': product.stock_status === 'out',
                                'bg-orange-100 text-orange-800 border-2 border-orange-300': product.stock_status === 'low',
                                'bg-yellow-100 text-yellow-800 border-2 border-yellow-300': product.stock_status === 'medium',
                                'bg-green-100 text-green-800 border-2 border-green-300': product.stock_status === 'good'
                            }"
                        >
                            <span 
                                class="inline-block w-2 h-2 rounded-full mr-1"
                                :class="{
                                    'bg-red-600': product.stock_status === 'out',
                                    'bg-orange-600': product.stock_status === 'low',
                                    'bg-yellow-600': product.stock_status === 'medium',
                                    'bg-green-600': product.stock_status === 'good'
                                }"
                            ></span>
                            {{ product.stock_label }}
                        </div>
                    </div>

                    <!-- Product Details -->
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

                        <p class="text-sm text-gray-600 mb-3">{{ product.category.name }}</p>

                        <!-- Stock Information -->
                        <div class="bg-gray-50 rounded-lg p-3 mb-3 border border-gray-200">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Total Stock:</span>
                                <span class="text-2xl font-bold" :class="{
                                    'text-red-600': product.stock_status === 'out',
                                    'text-orange-600': product.stock_status === 'low',
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

                        <!-- Price -->
                        <p class="text-xl font-bold text-blue-600 mb-4">₱{{ Number(product.base_price).toLocaleString() }}</p>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Link 
                                :href="`/owner/inventory/${product.inventory[0]?.id}/edit`"
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

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
                <svg class="h-24 w-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No Products in Inventory</h3>
                <p class="text-gray-600 mb-4">Start by adding your first product to inventory</p>
                <Link 
                    href="/owner/inventory/create"
                    class="text-blue-600 hover:text-blue-700 font-medium mt-2 inline-block"
                >
                    Add your first product
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="products.data.length > 0" class="mt-6 flex justify-center gap-2">
                <template v-for="link in products.links" :key="link.label">
                    <Link 
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'px-4 py-2 rounded-lg',
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        :class="[
                            'px-4 py-2 rounded-lg bg-white text-gray-700 opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <Teleport to="body">
            <div v-if="showStockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4" @click="closeStockModal">
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
                        <p class="text-sm text-gray-600 mb-2">{{ selectedProduct.name }}</p>
                        <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                            <p class="text-sm text-gray-700">Current Stock: <span class="font-bold text-blue-600">{{ selectedProduct.total_stock }}</span></p>
                        </div>
                    </div>

                    <form @submit.prevent="submitStockAdjustment">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Adjustment</label>
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
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-center font-bold text-lg"
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
                                <button type="button" @click="stockAdjustment = 10" class="flex-1 px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm hover:bg-gray-200">+10</button>
                                <button type="button" @click="stockAdjustment = 50" class="flex-1 px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm hover:bg-gray-200">+50</button>
                                <button type="button" @click="stockAdjustment = -10" class="flex-1 px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm hover:bg-gray-200">-10</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Reason (optional)</label>
                            <input 
                                v-model="stockReason"
                                type="text"
                                placeholder="e.g., New shipment arrived"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>

                        <div class="flex gap-3">
                            <button 
                                type="button"
                                @click="closeStockModal"
                                class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="adjustingStock"
                                class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50"
                            >
                                {{ adjustingStock ? 'Adjusting...' : 'Confirm' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || '');
const stockFilter = ref(props.filters.stock_status || '');

const showStockModal = ref(false);
const selectedProduct = ref(null);
const stockAdjustment = ref(0);
const stockReason = ref('');
const adjustingStock = ref(false);

onMounted(() => {
    console.log('[InventoryIndex] Component mounted', {
        product_count: props.products.data.length,
        total_pages: props.products.last_page
    });
});

const applyFilters = () => {
    router.get('/owner/inventory', {
        search: search.value,
        category_id: categoryFilter.value,
        stock_status: stockFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openStockModal = (product) => {
    console.log('[InventoryIndex] Opening stock modal for product', product.id);
    selectedProduct.value = product;
    stockAdjustment.value = 0;
    stockReason.value = '';
    showStockModal.value = true;
};

const closeStockModal = () => {
    showStockModal.value = false;
    selectedProduct.value = null;
    stockAdjustment.value = 0;
    stockReason.value = '';
};

const submitStockAdjustment = () => {
    console.log('[InventoryIndex] Submitting stock adjustment', {
        product_id: selectedProduct.value.id,
        adjustment: stockAdjustment.value
    });

    adjustingStock.value = true;

    router.post(`/owner/inventory/${selectedProduct.value.id}/adjust`, {
        adjustment: stockAdjustment.value,
        reason: stockReason.value,
    }, {
        onSuccess: () => {
            console.log('[InventoryIndex] Stock adjusted successfully');
            closeStockModal();
        },
        onError: (errors) => {
            console.error('[InventoryIndex] Stock adjustment failed', errors);
        },
        onFinish: () => {
            adjustingStock.value = false;
        }
    });
};

const deleteProduct = (id) => {
    if (confirm('Are you sure you want to delete this product? This will remove it from inventory permanently.')) {
        console.log('[InventoryIndex] Deleting product', id);
        router.delete(`/owner/inventory/${id}`);
    }
};
</script>
