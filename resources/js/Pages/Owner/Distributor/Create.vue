<template>
    <MainLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Become a Distributor</h1>
                <p class="text-gray-600 mt-2">Register your business to start selling on MedEquip</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Business Info -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Business Information</h2>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Company Name *</label>
                                <input 
                                    v-model="form.company_name" 
                                    type="text" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="form.errors.company_name" class="text-red-500 text-sm mt-1">{{ form.errors.company_name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Business Address *</label>
                                <textarea 
                                    v-model="form.address" 
                                    rows="3"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                ></textarea>
                                <p v-if="form.errors.address" class="text-red-500 text-sm mt-1">{{ form.errors.address }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number *</label>
                                    <input 
                                        v-model="form.contact_number" 
                                        type="tel" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <p v-if="form.errors.contact_number" class="text-red-500 text-sm mt-1">{{ form.errors.contact_number }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Business Email *</label>
                                    <input 
                                        v-model="form.email" 
                                        type="email" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b">Legal Documents</h2>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Valid ID (Government Issued) *</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition">
                                    <input 
                                        type="file" 
                                        @change="e => form.valid_id = e.target.files[0]"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        required
                                        class="w-full"
                                    />
                                    <p class="text-xs text-gray-500 mt-2">PDF, JPG, PNG (Max 2MB)</p>
                                </div>
                                <p v-if="form.errors.valid_id" class="text-red-500 text-sm mt-1">{{ form.errors.valid_id }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Business License / Permit *</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition">
                                    <input 
                                        type="file" 
                                        @change="e => form.business_license = e.target.files[0]"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        required
                                        class="w-full"
                                    />
                                    <p class="text-xs text-gray-500 mt-2">PDF, JPG, PNG (Max 2MB)</p>
                                </div>
                                <p v-if="form.errors.business_license" class="text-red-500 text-sm mt-1">{{ form.errors.business_license }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-blue-600 text-white px-6 py-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ form.processing ? 'Submitting Application...' : 'Submit Application' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const form = useForm({
    company_name: '',
    address: '',
    contact_number: '',
    email: '',
    valid_id: null,
    business_license: null,
});

const submit = () => {
    form.post('/owner/distributor/store', {
        forceFormData: true,
    });
};
</script>
