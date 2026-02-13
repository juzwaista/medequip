<template>
    <MainLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center text-sm text-gray-600 mb-4">
                    <Link href="/owner/inventory" class="hover:text-blue-600">Inventory Management</Link>
                    <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-900 font-medium">Add Product</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
                <p class="text-gray-600 mt-1">Create a new product and set initial stock level</p>
            </div>

            <!-- Error Messages -->
            <div v-if="errors && Object.keys(errors).length > 0" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800 mb-1">Please fix the following errors:</p>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow-md p-6">
                <!-- Product Details Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-200">Product Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Product Name -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Product Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g., Digital Blood Pressure Monitor"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select 
                                v-model="form.category_id"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Select a category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Base Price -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Price (₱) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model.number="form.base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Wholesale Price -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Wholesale Price (₱) <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input 
                                v-model.number="form.wholesale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Wholesale Min Quantity -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Wholesale Min Quantity <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input 
                                v-model.number="form.wholesale_min_qty"
                                type="number"
                                min="1"
                                placeholder="10"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Brand -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Brand <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input 
                                v-model="form.brand"
                                type="text"
                                placeholder="e.g., Omron, Philips"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Model -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Model <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input 
                                v-model="form.model"
                                type="text"
                                placeholder="e.g., HEM-7120, Series 3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                v-model="form.description"
                                required
                                rows="4"
                                placeholder="Describe the product features, specifications, and benefits..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                        </div>

                        <!-- Product Image -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Product Image <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <input 
                                        @change="handleImageUpload"
                                        type="file"
                                        accept="image/*"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Max size: 2MB. Formats: JPG, PNG, GIF</p>
                                </div>
                                <div v-if="imagePreview" class="w-24 h-24 rounded-lg overflow-hidden border-2 border-gray-300">
                                    <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Management Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-2 border-b border-gray-200">Initial Stock</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Initial Quantity -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Initial Quantity <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model.number="form.initial_quantity"
                                type="number"
                                min="0"
                                required
                                placeholder="0"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p class="text-xs text-gray-500 mt-1">How many units do you have in stock?</p>
                        </div>

                        <!-- Reorder Level -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Low Stock Alert Level <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model.number="form.reorder_level"
                                type="number"
                                min="0"
                                required
                                placeholder="10"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p class="text-xs text-gray-500 mt-1">You'll be notified when stock falls below this level</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <Link 
                        href="/owner/inventory"
                        class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium text-center"
                    >
                        Cancel
                    </Link>
                    <button 
                        type="submit"
                        :disabled="submitting"
                        class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ submitting ? 'Creating...' : 'Create Product' }}
                    </button>
                </div>
            </form>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    categories: Array,
});

const { props: pageProps } = usePage();
const errors = ref(pageProps.errors || {});

const form = ref({
    name: '',
    description: '',
    category_id: '',
    brand: '',
    model: '',
    base_price: '',
    wholesale_price: '',
    wholesale_min_qty: '',
    image: null,
    initial_quantity: 0,
    reorder_level: 10,
});

const imagePreview = ref(null);
const submitting = ref(false);

onMounted(() => {
    console.log('[InventoryCreate] Component mounted', {
        categories_count: props.categories.length
    });
});

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
        
        console.log('[InventoryCreate] Image selected', {
            name: file.name,
            size: file.size,
            type: file.type
        });
    }
};

const submitForm = () => {
    console.log('[InventoryCreate] Submitting form', {
        name: form.value.name,
        category_id: form.value.category_id,
        initial_quantity: form.value.initial_quantity
    });

    submitting.value = true;

    router.post('/owner/inventory', form.value, {
        onSuccess: () => {
            console.log('[InventoryCreate] Product created successfully');
        },
        onError: (errors) => {
            console.error('[InventoryCreate] Validation errors', errors);
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
};
</script>
