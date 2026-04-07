<template>
    <Head title="Contact Us" />
    <MainLayout>
        <div class="max-w-4xl mx-auto py-16 px-4">
            <h1 class="text-4xl font-bold text-gray-900 mb-6">Contact Us</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Info Section -->
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Get in Touch</h3>
                    <div class="space-y-4 text-gray-700">
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-blue-600 mr-3 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="font-semibold">Headquarters</p>
                                <p>Dasmariñas City, Cavite,<br>Philippines 4114</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="font-semibold">Email</p>
                                <p>contact@medequip.shop</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <div>
                                <p class="font-semibold">Phone</p>
                                <p>+63 912 345 6789</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Send Message Section -->
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Send a Message</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input 
                                v-model="form.name"
                                type="text" 
                                required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500"
                                :class="{'border-red-500': form.errors.name}"
                            >
                            <span v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500"
                                :class="{'border-red-500': form.errors.email}"
                            >
                            <span v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea 
                                v-model="form.message"
                                rows="4" 
                                required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500"
                                :class="{'border-red-500': form.errors.message}"
                            ></textarea>
                            <span v-if="form.errors.message" class="text-xs text-red-600 mt-1">{{ form.errors.message }}</span>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center justify-center shadow-lg"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ form.processing ? 'Sending...' : 'Send Message' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const form = useForm({
    name: '',
    email: '',
    message: '',
});

const submit = () => {
    form.post('/contact', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
