<template>
    <MainLayout>
        <div class="max-w-2xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Apply for a B2B Business Account</h1>
            <p class="text-gray-600 mb-6">
                Get access to wholesale pricing, bulk order discounts, and request custom quotes directly from distributors.
            </p>

            <form @submit.prevent="submit" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-6">
                <!-- Company Name -->
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name / Business Name</label>
                    <input
                        id="company_name"
                        v-model="form.company_name"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        required
                    />
                    <p v-if="form.errors.company_name" class="text-sm text-red-600 mt-1">{{ form.errors.company_name }}</p>
                </div>

                <!-- Business Type -->
                <div>
                    <label for="business_type" class="block text-sm font-medium text-gray-700">Business Type</label>
                    <select
                        id="business_type"
                        v-model="form.business_type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        required
                    >
                        <option value="hospital">Hospital</option>
                        <option value="clinic">Clinic</option>
                        <option value="pharmacy">Pharmacy</option>
                        <option value="distributor">Distributor / Reseller</option>
                        <option value="other">Other</option>
                    </select>
                    <p v-if="form.errors.business_type" class="text-sm text-red-600 mt-1">{{ form.errors.business_type }}</p>
                </div>

                <!-- TIN Number -->
                <div>
                    <label for="tin_number" class="block text-sm font-medium text-gray-700">Tax Identification Number (TIN)</label>
                    <input
                        id="tin_number"
                        v-model="form.tin_number"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        required
                    />
                    <p v-if="form.errors.tin_number" class="text-sm text-red-600 mt-1">{{ form.errors.tin_number }}</p>
                </div>

                <!-- Document Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business Registration Document</label>
                    <p class="text-xs text-gray-500 mb-2">Please upload your SEC, DTI, or equivalent business registration document (PDF, JPG, PNG. Max 5MB).</p>
                    
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative" :class="{'border-indigo-500 bg-indigo-50': dragover}" @dragover.prevent="dragover = true" @dragleave.prevent="dragover = false" @drop.prevent="handleDrop">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="document" class="relative cursor-pointer bg-transparent rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="document" name="document" type="file" class="sr-only" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                PDF, PNG, JPG up to 5MB
                            </p>
                        </div>
                    </div>
                    
                    <div v-if="form.document" class="mt-2 text-sm text-gray-900 bg-gray-50 p-2 rounded border border-gray-200 flex items-center justify-between">
                        <span class="truncate">{{ form.document.name }}</span>
                        <button type="button" @click="form.document = null" class="text-red-600 hover:text-red-800 ml-2 font-medium">Remove</button>
                    </div>
                    
                    <p v-if="form.errors.document" class="text-sm text-red-600 mt-1">{{ form.errors.document }}</p>
                </div>

                <div class="pt-4 border-t border-gray-200 flex justify-end">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                    </button>
                </div>
            </form>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const dragover = ref(false);

const form = useForm({
    company_name: '',
    business_type: 'hospital',
    tin_number: '',
    document: null,
});

const handleFileChange = (e) => {
    if (e.target.files.length > 0) {
        form.document = e.target.files[0];
    }
};

const handleDrop = (e) => {
    dragover.value = false;
    if (e.dataTransfer.files.length > 0) {
        form.document = e.dataTransfer.files[0];
    }
};

const submit = () => {
    form.post(route('business.store'));
};
</script>
