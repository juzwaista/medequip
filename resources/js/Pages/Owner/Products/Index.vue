<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Products</h1>
                    <p class="text-gray-600 mt-1">Manage your product catalog</p>
                </div>
                <Link 
                    href="/owner/products/create"
                    class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition font-bold shadow-lg"
                >
                    + Add New Product
                </Link>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                <input 
                    v-model="search"
                    @input="applySearch"
                    type="text"
                    placeholder="Search products..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <!-- Products Grid -->
            <div v-if="products.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
                <div v-for="product in products.data" :key="product.id" class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition flex flex-col">
                    <div class="w-full aspect-square bg-gray-200 flex items-center justify-center relative shrink-0 overflow-hidden">
                        <img v-if="product.image_path" :src="`/storage/${product.image_path}`" :alt="product.name" class="w-full h-full object-cover" />
                        <svg v-else class="h-12 w-12 sm:h-16 sm:w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        
                        <div class="absolute top-2 right-2">
                             <span 
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold shadow-sm',
                                    product.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                ]"
                            >
                                {{ product.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-3 sm:p-4 flex flex-col flex-1 h-full">
                        <div class="mb-1.5 min-h-[22px] sm:min-h-[26px]">
                            <h3 class="font-bold text-sm sm:text-base text-gray-900 truncate">{{ product.name }}</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-gray-600 mb-2 truncate min-h-[16px]">{{ product.category.name }}</p>
                        
                        <div class="mt-auto">
                            <p class="text-base sm:text-lg font-bold text-blue-600 mb-2">₱{{ Number(product.price).toLocaleString() }}</p>
                            <div class="text-[11px] sm:text-xs text-gray-600 mb-3 bg-gray-50 p-2 rounded-lg border border-gray-100 min-h-[34px] flex items-center">
                                <span><span class="font-semibold">Stock:</span> <span class="font-bold text-gray-800">{{ product.total_stock }}</span> units</span>
                            </div>
                            <div class="flex gap-1.5">
                                <Link 
                                    :href="`/owner/products/${product.id}/edit`"
                                    class="flex-1 text-center bg-blue-600 text-white hover:bg-blue-700 text-[11px] sm:text-xs px-2 py-1.5 rounded-lg transition font-medium"
                                >
                                    Edit
                                </Link>
                                <button 
                                    @click="deleteProduct(product.id)"
                                    class="w-8 h-8 flex items-center justify-center border border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition shrink-0"
                                    title="Delete product"
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

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
                <svg class="h-24 w-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No Products Yet</h3>
                <p class="text-gray-600 mb-4">Start building your catalog by adding your first product</p>
                <Link 
                    href="/owner/products/create"
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
    </OwnerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const performSearch = () => {
    router.get('/owner/products', { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteProduct = (id) => {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/owner/products/${id}`);
    }
};
</script>
