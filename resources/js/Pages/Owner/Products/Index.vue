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
            <div v-if="products.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="product in products.data" :key="product.id" class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition">
                    <div class="aspect-square bg-gray-200 flex items-center justify-center">
                        <img v-if="product.image_path" :src="`/storage/${product.image_path}`" :alt="product.name" class="w-full h-full object-cover" />
                        <svg v-else class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-bold text-lg text-gray-900">{{ product.name }}</h3>
                            <span 
                                :class="[
                                    'px-2 py-1 rounded-full text-xs font-semibold',
                                    product.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                ]"
                            >
                                {{ product.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">{{ product.category.name }}</p>
                        <p class="text-xl font-bold text-blue-600 mb-2">₱{{ Number(product.price).toLocaleString() }}</p>
                        <div class="text-sm text-gray-600 mb-4">
                            <span class="font-semibold">Stock:</span> {{ product.total_stock }} units
                        </div>
                        <div class="flex gap-2">
                            <Link 
                                :href="`/owner/products/${product.id}/edit`"
                                class="text-blue-600 hover:text-blue-900 text-sm"
                            >
                                Edit
                            </Link>
                            <button 
                                @click="deleteProduct(product.id)"
                                class="px-4 py-2 border-2 border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition font-medium"
                            >
                                Delete
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
