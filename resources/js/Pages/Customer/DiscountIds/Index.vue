<template>
    <MainLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Saved Discount IDs</h1>
                    <p class="text-gray-600 mt-2">Manage your SC/PWD IDs for faster checkout</p>
                </div>
                <button 
                    @click="showAddForm = true"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center gap-2"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New ID
                </button>
            </div>

            <!-- Add/Edit Form Modal -->
            <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ editingDiscountId ? 'Edit ID' : 'Add New ID' }}</h2>
                    <div v-if="Object.keys(errors).length" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ Object.values(errors)[0] }}
                    </div>
                    
                    <form @submit.prevent="saveDiscountId" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Label (Optional)</label>
                            <input 
                                v-model="form.label"
                                type="text"
                                placeholder="e.g., My SC ID"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="errors.label" class="text-red-500 text-sm mt-1">{{ errors.label }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label
                                :class="[
                                    'flex items-center justify-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition',
                                    form.discount_type === 'senior'
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 bg-white',
                                ]"
                            >
                                <input
                                    type="radio"
                                    v-model="form.discount_type"
                                    value="senior"
                                    class="sr-only"
                                />
                                <span class="text-sm font-bold">Senior Citizen</span>
                            </label>
                            <label
                                :class="[
                                    'flex items-center justify-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition',
                                    form.discount_type === 'pwd'
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 bg-white',
                                ]"
                            >
                                <input
                                    type="radio"
                                    v-model="form.discount_type"
                                    value="pwd"
                                    class="sr-only"
                                />
                                <span class="text-sm font-bold">PWD</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Full Name (must match ID) *</label>
                                <input 
                                    v-model="form.id_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="errors.id_name" class="text-red-500 text-sm mt-1">{{ errors.id_name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">ID Number *</label>
                                <input 
                                    v-model="form.id_number"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="errors.id_number" class="text-red-500 text-sm mt-1">{{ errors.id_number }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">ID Photo *</label>
                            <input 
                                type="file"
                                @change="e => form.id_image = e.target.files[0]"
                                :required="!editingDiscountId"
                                accept="image/*"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p v-if="editingDiscountId" class="text-xs text-gray-500 mt-1">Leave empty to keep the existing photo.</p>
                            <p v-if="errors.id_image" class="text-red-500 text-sm mt-1">{{ errors.id_image }}</p>
                        </div>

                        <div class="flex items-center">
                            <input 
                                v-model="form.is_default"
                                type="checkbox"
                                class="h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                            />
                            <label class="ml-3 text-sm font-medium text-gray-700">Set as default ID</label>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button 
                                type="button"
                                @click="cancelForm"
                                class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                                :disabled="form.processing"
                            >
                                {{ editingDiscountId ? 'Update ID' : 'Save ID' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- IDs List -->
            <div v-if="discountIds.length > 0" class="space-y-4">
                <div 
                    v-for="discountId in discountIds" 
                    :key="discountId.id"
                    class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ discountId.label || 'Discount ID' }}</h3>
                                <span v-if="discountId.is_default" class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-semibold">
                                    Default
                                </span>
                                <span class="bg-emerald-100 text-emerald-800 text-xs px-3 py-1 rounded-full font-semibold uppercase">
                                    {{ discountId.discount_type }}
                                </span>
                            </div>
                            <p class="font-semibold text-gray-900">{{ discountId.id_name }}</p>
                            <p class="text-gray-600">ID No: {{ discountId.id_number }}</p>
                        </div>

                        <div class="flex gap-2">
                            <button 
                                v-if="!discountId.is_default"
                                @click="setAsDefault(discountId)"
                                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                            >
                                Set Default
                            </button>
                            <button 
                                @click="editDiscountId(discountId)"
                                class="text-gray-600 hover:text-gray-700"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button 
                                @click="deleteDiscountId(discountId)"
                                class="text-red-600 hover:text-red-700"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
                <svg class="h-20 w-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Saved Discount IDs</h3>
                <p class="text-gray-600 mb-6">Add your Senior Citizen or PWD ID for faster checkout</p>
                <button 
                    @click="showAddForm = true"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                >
                    Add Your First ID
                </button>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    discountIds: Array,
});
const page = usePage();
const errors = computed(() => page.props.errors || {});

const showAddForm = ref(false);
const editingDiscountId = ref(null);

const form = useForm({
    label: '',
    discount_type: 'senior',
    id_name: '',
    id_number: '',
    id_image: null,
    is_default: false,
});

const resetForm = () => {
    form.reset();
    form.clearErrors();
    editingDiscountId.value = null;
};

const cancelForm = () => {
    showAddForm.value = false;
    resetForm();
};

const editDiscountId = (discountId) => {
    editingDiscountId.value = discountId;
    form.label = discountId.label || '';
    form.discount_type = discountId.discount_type;
    form.id_name = discountId.id_name;
    form.id_number = discountId.id_number;
    form.id_image = null;
    form.is_default = discountId.is_default;
    
    showAddForm.value = true;
};

const saveDiscountId = () => {
    if (editingDiscountId.value) {
        form.post(`/discount-ids/${editingDiscountId.value.id}`, {
            onSuccess: () => {
                showAddForm.value = false;
                resetForm();
            }
        });
    } else {
        form.post('/discount-ids', {
            onSuccess: () => {
                showAddForm.value = false;
                resetForm();
            }
        });
    }
};

const setAsDefault = (discountId) => {
    router.post(`/discount-ids/${discountId.id}/default`);
};

const deleteDiscountId = (discountId) => {
    if (confirm('Are you sure you want to delete this discount ID?')) {
        router.delete(`/discount-ids/${discountId.id}`);
    }
};

import { computed } from 'vue';
</script>
