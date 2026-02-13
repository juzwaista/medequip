<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-8 text-sm text-gray-600">
                <Link href="/products" class="hover:text-blue-600 transition">Products</Link>
                <span class="mx-2">/</span>
                <Link :href="`/category/${product.category.id}`" class="hover:text-blue-600 transition">
                    {{ product.category.name }}
                </Link>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ product.name }}</span>
            </nav>

            <!-- Product Detail -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
                <!-- Product Image -->
                <div>
                    <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl shadow-lg aspect-square flex items-center justify-center overflow-hidden">
                        <img 
                            v-if="product.image_url"
                            :src="product.image_url" 
                            :alt="product.name"
                            class="w-full h-full object-cover"
                        />
                        <svg v-else class="h-32 w-32 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <!-- Product Info -->
                <div>
                    <div class="mb-6">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-semibold">
                                    {{ product.product_type === 'equipment' ? 'Equipment' : 'Consumable' }}
                                </span>
                                <span v-if="product.has_warranty" class="ml-2 bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-semibold">
                                    {{ product.warranty_months }} Month Warranty
                                </span>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold text-gray-900 mt-4 mb-2">{{ product.name }}</h1>
                        <p class="text-xl text-gray-600">{{ product.brand || 'Generic Brand' }} {{ product.model ? `- ${product.model}` : '' }}</p>
                    </div>

                    <!-- Distributor Info -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-200">
                        <p class="text-sm text-gray-600 mb-1">Sold by</p>
                        <Link 
                            v-if="product.distributor.slug"
                            :href="`/seller/${product.distributor.slug}`"
                            class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                        >
                            {{ product.distributor.company_name }}
                        </Link>
                        <p v-else class="font-semibold text-gray-900">{{ product.distributor.company_name }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ product.distributor.address }}</p>
                    </div>

                    <!-- Pricing -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 mb-6 border border-blue-200">
                        <div class="mb-4">
                            <p class="text-sm text-gray-700 mb-1">Retail Price</p>
                            <p class="text-4xl font-bold text-blue-600">
                                ₱{{ Number(product.base_price).toLocaleString() }}
                            </p>
                        </div>

                        <div v-if="product.wholesale_price" class="bg-white rounded-xl p-4 border border-blue-200">
                            <div class="flex items-center mb-2">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-semibold text-gray-900">Wholesale Price Available</span>
                            </div>
                            <p class="text-2xl font-bold text-green-600 mb-1">
                                ₱{{ Number(product.wholesale_price).toLocaleString() }}
                            </p>
                            <p class="text-sm text-gray-600">
                                When you buy {{ product.wholesale_min_qty }}+ pieces
                                <span class="font-semibold text-green-600">
                                    (Save ₱{{ (product.base_price - product.wholesale_price).toLocaleString() }} per piece!)
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Stock Info -->
                    <div class="mb-6">
                        <div class="flex items-center text-lg mb-3">
                            <svg class="h-6 w-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold text-gray-900">
                                {{ availableStock }} units available
                            </span>
                        </div>
                        
                        <!-- Branch Stock -->
                        <div v-if="product.inventory.length > 0" class="space-y-2">
                            <p class="text-sm font-semibold text-gray-700 mb-2">Available at:</p>
                            <div v-for="inv in product.inventory" :key="inv.id" class="flex justify-between text-sm p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-700">
                                    {{ inv.branch ? inv.branch.branch_name : 'Main Warehouse' }}
                                </span>
                                <span class="font-semibold" :class="inv.quantity <= inv.reorder_level ? 'text-orange-600' : 'text-green-600'">
                                    {{ inv.quantity - inv.reserved_quantity }} pcs
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Add to Cart -->
                    <div class="flex gap-4 mb-6">
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                            <input 
                                type="number"
                                v-model.number="quantity"
                                min="1"
                                :max="availableStock"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                            />
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button 
                            @click="addToCart"
                            :disabled="adding"
                            class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-4 rounded-xl hover:shadow-xl transition font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <svg v-if="adding" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ adding ? 'Adding...' : 'Add to Cart' }}
                        </button>
                        <button class="px-6 py-4 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash.success || $page.props.flash.error" class="fixed top-20 right-4 z-50">
                <div v-if="$page.props.flash.success" class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash.error" class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ $page.props.flash.error }}
                </div>
            </div>


            <!-- Product Description -->
            <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Product Description</h2>
                <p class="text-gray-700 leading-relaxed">{{ product.description }}</p>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">SKU</p>
                        <p class="font-semibold">{{ product.sku }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Category</p>
                        <p class="font-semibold">{{ product.category.name }}</p>
                    </div>
                    <div v-if="product.has_expiry" class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Expiry Tracking</p>
                        <p class="font-semibold text-orange-600">Yes</p>
                    </div>
                    <div v-if="product.has_warranty" class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Warranty</p>
                        <p class="font-semibold text-green-600">{{ product.warranty_months }} Months</p>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div v-if="relatedProducts.length">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Products</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link 
                        v-for="related in relatedProducts" 
                        :key="related.id"
                        :href="`/products/${related.id}`"
                        class="group bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden"
                    >
                        <div class="h-40 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition line-clamp-2 mb-2">
                                {{ related.name }}
                            </h3>
                            <p class="text-xl font-bold text-blue-600">
                                ₱{{ Number(related.base_price).toLocaleString() }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    product: Object,
    relatedProducts: Array,
    totalStock: Number,
    availableStock: Number,
});

const quantity = ref(1);
const adding = ref(false);

const addToCart = () => {
    adding.value = true;
    
    router.post('/cart/add', {
        product_id: props.product.id,
        quantity: quantity.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            adding.value = false;
        }
    });
};
</script>

