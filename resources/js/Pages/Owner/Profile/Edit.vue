<template>
    <OwnerLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Business Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Basic Information -->
                            <div class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Company Name</label>
                                        <input 
                                            v-model="form.company_name"
                                            type="text"
                                            disabled
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm cursor-not-allowed"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            Company name cannot be changed as it's verified against business permits. Contact support to request changes.
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Profile URL</label>
                                        <div class="flex mt-1">
                                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                /seller/
                                            </span>
                                            <input 
                                                v-model="form.slug"
                                                @input="checkSlug"
                                                type="text"
                                                required
                                                class="block w-full rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                        </div>
                                        <p v-if="slugStatus" :class="slugStatus.available ? 'text-green-600' : 'text-red-600'" class="text-xs mt-1">
                                            {{ slugStatus.message }}
                                        </p>
                                        <p v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea 
                                        v-model="form.description"
                                        rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Tell customers about your business..."
                                    ></textarea>
                                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input 
                                            v-model="form.phone"
                                            type="tel"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Website</label>
                                        <input 
                                            v-model="form.website"
                                            type="url"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Images -->
                            <div class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Images</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Logo -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                                        <div class="flex items-center space-x-4">
                                            <div class="h-24 w-24 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-200">
                                                <img 
                                                    v-if="logoPreview" 
                                                    :src="logoPreview" 
                                                    class="h-full w-full object-cover"
                                                />
                                                <img 
                                                    v-else-if="distributor.logo_path" 
                                                    :src="`/storage/${distributor.logo_path}`" 
                                                    class="h-full w-full object-cover"
                                                />
                                                <div v-else class="h-full w-full flex items-center justify-center text-gray-400">
                                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <input 
                                                type="file" 
                                                @change="handleLogoUpload"
                                                accept="image/*"
                                                class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                            />
                                        </div>
                                    </div>

                                    <!-- Cover Photo -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Photo</label>
                                        <div class="w-full h-32 rounded-lg overflow-hidden bg-gray-100 border-2 border-gray-200 mb-2">
                                            <img 
                                                v-if="coverPreview" 
                                                :src="coverPreview" 
                                                class="h-full w-full object-cover"
                                            />
                                            <img 
                                                v-else-if="distributor.cover_photo_path" 
                                                :src="`/storage/${distributor.cover_photo_path}`" 
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="h-full w-full flex items-center justify-center text-gray-400">
                                                <span class="text-sm">No cover photo</span>
                                            </div>
                                        </div>
                                        <input 
                                            type="file" 
                                            @change="handleCoverUpload"
                                            accept="image/*"
                                            class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Link 
                                    :href="`/seller/${form.slug || distributor.slug}`" 
                                    target="_blank"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                                >
                                    View Public Profile
                                </Link>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps({
    distributor: Object
});

const form = useForm({
    company_name: props.distributor.company_name,
    slug: props.distributor.slug,
    description: props.distributor.description || '',
    phone: props.distributor.phone || '',
    website: props.distributor.website || '',
    logo: null,
    cover_photo: null,
    _method: 'PUT'
});

const logoPreview = ref(null);
const coverPreview = ref(null);
const slugStatus = ref(null);

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleCoverUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_photo = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

const checkSlug = debounce(async () => {
    if (!form.slug) return;
    
    try {
        const response = await axios.post(route('owner.profile.checkSlug'), { slug: form.slug });
        slugStatus.value = {
            available: response.data.available,
            message: response.data.available ? 'URL is available' : 'URL is already taken'
        };
    } catch (error) {
        console.error('Error checking slug:', error);
    }
}, 500);

const submit = () => {
    form.post(route('owner.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // handle success
        }
    });
};
</script>
