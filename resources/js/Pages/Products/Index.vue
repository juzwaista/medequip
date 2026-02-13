<template>
    <MainLayout>
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                <h1 class="text-4xl font-bold mb-4">Browse Medical Equipment</h1>
                <p class="text-blue-100 text-lg">Discover quality medical products from verified distributors in Cavite</p>
                
                <!-- Search Bar -->
                <div class="mt-8 max-w-2xl mx-auto">
                    <div class="relative">
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text" 
                            placeholder="Search products by name, brand, or SKU..." 
                            class="w-full px-6 py-4 rounded-xl text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg"
                        />
                        <button 
                            @click="applyFilters"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition font-medium"
                        >
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Filters Sidebar -->
                <aside class="lg:w-64 flex-shrink-0">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24 space-y-6">
                        <h3 class="font-bold text-lg text-gray-900 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                        </h3>

                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                            <select 
                                v-model="filters.category" 
                                @change="applyFilters"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Categories</option>
                                <template v-for="category in categories" :key="category.id">
                                    <option :value="category.id" disabled class="font-bold text-gray-700 bg-gray-100">
                                        {{ category.name }}
                                    </option>
                                    <option 
                                        v-for="child in category.children" 
                                        :key="child.id" 
                                        :value="child.id"
                                        class="pl-6"
                                    >
                                        {{ child.name }}
                                    </option>
                                </template>
                            </select>
                        </div>

                        <!-- Product Type Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                            <select 
                                v-model="filters.type" 
                                @change="applyFilters"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Types</option>
                                <option value="equipment">Equipment</option>
                                <option value="consumable">Consumable</option>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price Range</label>
                            <div class="space-y-2">
                                <input 
                                    v-model.number="filters.min_price"
                                    @change="applyFilters"
                                    type="number" 
                                    placeholder="Min" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <input 
                                    v-model.number="filters.max_price"
                                    @change="applyFilters"
                                    type="number" 
                                    placeholder="Max" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Reset Button -->
                        <button 
                            @click="resetFilters"
                            class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                        >
                            Reset Filters
                        </button>
                    </div>
                </aside>

                <!-- Products Grid -->
                <div class="flex-1">
                    <!-- Results Header -->
                    <div class="flex justify-between items-center mb-6">
                        <p class="text-gray-600">
                            <span class="font-semibold text-gray-900">{{ products.total }}</span> products found
                        </p>
                        
                        <select 
                            v-model="filters.sort" 
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="newest">Newest First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name">Name: A-Z</option>
                        </select>
                    </div>

                    <!-- Products Grid -->
                    <div v-if="products.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <Link 
                            v-for="product in products.data" 
                            :key="product.id"
                            :href="`/products/${product.id}`"
                            class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
                        >
                            <!-- Product Image -->
                            <div class="relative h-48 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                                <img 
                                    v-if="product.image_url"
                                    :src="product.image_url" 
                                    :alt="product.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="absolute inset-0 flex items-center justify-center">
                                    <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                
                                <!-- Wholesale Badge -->
                                <div v-if="product.wholesale_price" class="absolute top-3 right-3 bg-green-500 text-white text-xs px-3 py-1 rounded-full font-semibold shadow-lg">
                                    Wholesale Available
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-5">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition line-clamp-2">
                                        {{ product.name }}
                                    </h3>
                                </div>
                                
                                <p class="text-sm text-gray-500 mb-1">{{ product.brand || 'Generic' }}</p>
                                <Link 
                                    :href="`/seller/${product.distributor.slug}`"
                                    class="text-sm text-blue-600 hover:text-blue-800 hover:underline mb-3 block"
                                    @click.stop
                                >
                                    by {{ product.distributor.company_name }}
                                </Link>

                                <div class="flex items-baseline justify-between">
                                    <div>
                                        <span class="text-2xl font-bold text-blue-600">
                                            ₱{{ Number(product.base_price).toLocaleString() }}
                                        </span>
                                        <p v-if="product.wholesale_price" class="text-xs text-gray-500 mt-1">
                                            Wholesale: ₱{{ Number(product.wholesale_price).toLocaleString() }} 
                                            ({{ product.wholesale_min_qty }}+ pcs)
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center text-sm text-gray-600">
                                    <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    In Stock
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-16">
                        <svg class="h-24 w-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your filters or search query</p>
                        <button @click="resetFilters" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Clear Filters
                        </button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products.data.length" class="mt-8 flex justify-center">
                        <nav class="flex space-x-2">
                            <Link
                                v-for="link in products.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-4 py-2 rounded-lg transition font-medium',
                                    link.active 
                                        ? 'bg-blue-600 text-white' 
                                        : link.url 
                                            ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' 
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    distributors: Array,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const filters = reactive({
    category: props.filters.category || '',
    distributor: props.filters.distributor || '',
    type: props.filters.type || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    sort: props.filters.sort || 'newest',
});

const applyFilters = () => {
    router.get('/products', {
        search: searchQuery.value,
        ...filters
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchQuery.value = '';
    filters.category = '';
    filters.distributor = '';
    filters.type = '';
    filters.min_price = '';
    filters.max_price = '';
    filters.sort = 'newest';
    applyFilters();
};
</script>
