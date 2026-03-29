<template>
    <MainLayout>
    <div class="relative bg-slate-800 overflow-hidden min-h-[400px] flex items-center">
        <div class="absolute inset-0 opacity-[0.03] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px]"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center w-full">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-6 text-white tracking-tight">
                Welcome to <br class="hidden md:block"/> 
                <span class="text-blue-400">MedEquip.</span>
            </h1>
            
            <p class="text-slate-300 text-lg md:text-xl max-w-2xl mx-auto font-normal mb-10">
                Sourcing healthcare products made easy. Shop for essential medical supplies directly from verified distributors in Cavite.
            </p>
            
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-xl p-2 flex items-center focus-within:ring-4 focus-within:ring-blue-500/30 transition-shadow">
                    <div class="pl-4 pr-2 text-slate-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    
                    <input 
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text" 
                        placeholder="Search surgical tape, stethoscopes, gloves..." 
                        class="w-full bg-transparent px-2 py-3 text-slate-900 placeholder-slate-400 text-lg focus:outline-none border-none truncate"
                    />
                    
                    <button 
                        @click="applyFilters"
                        class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors ml-2"
                    >
                        Search
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="isFilterOpen" @click="isFilterOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-slate-50">
        <div class="flex flex-col lg:flex-row gap-8">
            <aside :class="['fixed inset-y-0 left-0 z-50 w-[80vw] max-w-sm bg-white shadow-2xl transform lg:transform-none lg:static lg:w-64 lg:bg-transparent lg:shadow-none transition-transform duration-300 ease-in-out lg:flex-shrink-0', isFilterOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']">
                <div class="lg:hidden px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-white">
                    <h3 class="font-bold text-lg text-slate-900">Filters</h3>
                    <button @click="isFilterOpen = false" class="p-2 text-slate-500 hover:text-slate-900 bg-slate-100 rounded-lg transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-white lg:rounded-xl lg:border lg:border-slate-200 p-6 lg:sticky lg:top-24 space-y-6 h-full lg:h-auto overflow-y-auto lg:overflow-visible pb-24 lg:pb-6">
                    <h3 class="hidden lg:flex font-bold text-lg text-slate-900 items-center border-b border-slate-100 pb-4">
                        <svg class="h-5 w-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                    </h3>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category</label>
                        <select 
                            v-model="filters.category" 
                            @change="applyFilters"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">All Categories</option>
                            <template v-for="category in categories" :key="category.id">
                                <option :value="category.id" disabled class="font-bold text-slate-700 bg-slate-50">
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

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Type</label>
                        <select 
                            v-model="filters.type" 
                            @change="applyFilters"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">All Types</option>
                            <option value="equipment">Equipment</option>
                            <option value="consumable">Consumable</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Price Range (₱)</label>
                        <div class="flex items-center space-x-2">
                            <input 
                                v-model.number="filters.min_price"
                                @change="applyFilters"
                                type="number" 
                                placeholder="Min" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span class="text-slate-400">-</span>
                            <input 
                                v-model.number="filters.max_price"
                                @change="applyFilters"
                                type="number" 
                                placeholder="Max" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <button 
                        @click="resetFilters"
                        class="w-full px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold mt-4"
                    >
                        Reset Filters
                    </button>
                </div>
            </aside>

            <div class="flex-1">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <p class="text-slate-600 text-sm">
                        Showing <span class="font-bold text-slate-900">{{ products.total }}</span> products
                    </p>
                    
                    <select 
                        v-model="filters.sort" 
                        @change="applyFilters"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm"
                    >
                        <option value="newest">Newest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="name">Name: A-Z</option>
                    </select>
                </div>

                <div v-if="products.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="product in products.data"
                        :key="product.id"
                        class="group bg-white rounded-xl border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200 overflow-hidden flex flex-col"
                    >
                        <Link :href="`/products/${product.id}`" class="block flex-shrink-0 relative border-b border-slate-100">
                            <div class="relative h-48 bg-slate-50 overflow-hidden p-4 flex items-center justify-center">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="max-w-full max-h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-slate-300">
                                    <svg class="h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs font-medium">No Image</span>
                                </div>
                                
                                <div v-if="product.wholesale_price" class="absolute top-3 left-3 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] uppercase tracking-wide px-2 py-1 rounded font-bold shadow-sm">
                                    Wholesale
                                </div>
                            </div>
                        </Link>

                        <div class="p-5 flex flex-col flex-1">
                            <p class="text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">{{ product.brand || 'Generic' }}</p>
                            
                            <Link :href="`/products/${product.id}`" class="block mb-1 group/title">
                                <h3 class="font-bold text-slate-900 group-hover/title:text-blue-600 transition-colors line-clamp-2 leading-snug">
                                    {{ product.name }}
                                </h3>
                            </Link>
                            
                            <div class="text-sm text-slate-500 mb-4 flex items-center gap-1">
                                <span>Distributor:</span>
                                <Link
                                    :href="`/seller/${product.distributor.slug}`"
                                    class="font-medium text-blue-600 hover:text-blue-800 hover:underline truncate"
                                >
                                    {{ product.distributor.company_name }}
                                </Link>
                            </div>

                            <div class="flex items-end justify-between mb-5 mt-auto">
                                <div>
                                    <span class="text-2xl font-extrabold text-slate-900">
                                        ₱{{ Number(product.base_price).toLocaleString() }}
                                    </span>
                                    <p v-if="product.wholesale_price" class="text-xs text-slate-500 mt-1 font-medium">
                                        ₱{{ Number(product.wholesale_price).toLocaleString() }} for {{ product.wholesale_min_qty }}+
                                    </p>
                                </div>
                                
                                <span class="flex items-center text-xs text-emerald-700 bg-emerald-50 px-2 py-1 rounded font-semibold border border-emerald-200">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                    In Stock
                                </span>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center gap-2">
                                <button
                                    @click="addToCart(product.id)"
                                    :disabled="addingToCart === product.id"
                                    class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-blue-600 text-white font-semibold text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:bg-slate-300 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="addingToCart === product.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <svg v-else-if="addedProductId !== product.id" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>{{ addedProductId === product.id ? 'Added' : 'Add to Cart' }}</span>
                                </button>
                                
                                <Link
                                    :href="`/products/${product.id}`"
                                    class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg border border-slate-300 text-slate-600 hover:border-blue-600 hover:text-blue-600 transition-colors bg-white"
                                    title="View Details"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20 bg-white rounded-xl border border-slate-200">
                    <svg class="h-16 w-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h3 class="text-lg font-bold text-slate-900 mb-1">No products found</h3>
                    <p class="text-slate-500 mb-6 text-sm">We couldn't find any products matching your filters.</p>
                    <button @click="resetFilters" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-colors">
                        Clear Filters
                    </button>
                </div>

                <div v-if="products.data.length" class="mt-10 flex justify-center">
                    <nav class="flex flex-wrap justify-center gap-2">
                        <Link
                            v-for="link in products.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-4 py-2 rounded-lg transition-colors text-sm font-semibold border',
                                link.active 
                                    ? 'bg-blue-600 text-white border-blue-600 shadow-sm' 
                                    : link.url 
                                        ? 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300' 
                                        : 'bg-slate-50 text-slate-400 border-slate-200 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <button 
        @click="isFilterOpen = true"
        class="lg:hidden fixed bottom-24 right-4 z-40 bg-blue-600 text-white rounded-full p-4 shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-colors flex items-center justify-center"
        style="bottom: calc(5rem + env(safe-area-inset-bottom, 0px))"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
    </button>
</MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';

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

const isFilterOpen = ref(false);

const addingToCart = ref(null);
const addedProductId = ref(null);

const addToCart = async (productId) => {
    addingToCart.value = productId;
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await axios.post('/cart/add', { product_id: productId, quantity: 1 }, {
            headers: { 'X-CSRF-TOKEN': token }
        });
        addedProductId.value = productId;
        setTimeout(() => { addedProductId.value = null; }, 2200);
        window.dispatchEvent(new CustomEvent('cart-updated'));
    } catch (e) {
        if (e?.response?.status === 401) {
            router.visit('/login');
        } else if (e?.response?.status === 419) {
            // CSRF expired - reload the page to get a fresh token
            window.location.reload();
        } else {
            alert(e?.response?.data?.message || 'Could not add to cart. Please try again.');
        }
    } finally {
        addingToCart.value = null;
    }
};

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
