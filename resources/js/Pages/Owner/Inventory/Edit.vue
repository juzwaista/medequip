<template>
    <MainLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Product</h1>
                <p class="text-gray-600 mt-2">Update product information</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <!-- Current Image Preview -->
                        <div v-if="inventory.product.image_path" class="flex justify-center mb-4">
                            <img :src="`/storage/${inventory.product.image_path}`" alt="Current product image" class="h-40 w-40 object-cover rounded-lg" />
                        </div>

                        <!-- Product Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                            <input 
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                            <select 
                                v-model="form.category_id"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</p>
                        </div>

                        <!-- Brand -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                            <input 
                                v-model="form.brand"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Model -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Model</label>
                            <input 
                                v-model="form.model"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description *</label>
                            <textarea 
                                v-model="form.description"
                                rows="4"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
                        </div>

                        <!-- Retail Price -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Retail Price *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                                <input 
                                    v-model="form.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <p v-if="form.errors.price" class="text-red-500 text-sm mt-1">{{ form.errors.price }}</p>
                        </div>

                        <!-- Wholesale Price (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Wholesale Price</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                                <input 
                                    v-model="form.wholesale_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <p v-if="form.errors.wholesale_price" class="text-red-500 text-sm mt-1">{{ form.errors.wholesale_price }}</p>
                        </div>

                        <!-- Min Wholesale Quantity -->
                        <div v-if="form.wholesale_price">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Minimum Wholesale Quantity</label>
                            <input 
                                v-model="form.wholesale_min_quantity"
                                type="number"
                                min="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.wholesale_min_quantity" class="text-red-500 text-sm mt-1">{{ form.errors.wholesale_min_quantity }}</p>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <input 
                                v-model="form.is_active"
                                type="checkbox"
                                id="is_active"
                                class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="is_active" class="ml-2 text-sm font-semibold text-gray-700">Product is active (visible to customers)</label>
                        </div>

                        <!-- Product Image -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Update Product Image</label>
                            <input 
                                type="file"
                                @change="e => form.image = e.target.files[0]"
                                accept="image/*"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image</p>
                            <p v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Updating...' : 'Update Product' }}
                        </button>
                        <Link 
                            href="/owner/inventory"
                            class="px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    inventory: Object,
    categories: Array,
});

const product = props.inventory.product;

const form = useForm({
    name: product.name,
    category_id: product.category_id,
    brand: product.brand || '',
    model: product.model || '',
    description: product.description,
    price: product.base_price,
    wholesale_price: product.wholesale_price || '',
    wholesale_min_quantity: product.wholesale_min_qty || '',
    is_active: product.is_active,
    image: null,
});

const submit = () => {
    form.post(`/owner/products/${product.id}`, {
        _method: 'put',
        forceFormData: true,
    });
};
</script>
