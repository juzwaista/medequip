<template>
    <MainLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 p-12 text-center">
                <div v-if="$page.props.auth.user.distributor?.status === 'pending'">
                    <div class="w-20 h-20 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Application Pending Approval</h2>
                    <p class="text-lg text-gray-600 mb-8">
                        Your distributor application has been submitted and is currently being reviewed by our administration team. 
                        We will notify you once your account has been approved.
                    </p>
                </div>
                <div v-else-if="$page.props.auth.user.distributor?.status === 'rejected'">
                    <div class="w-20 h-20 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-red-600 mb-4">Application Rejected</h2>
                    <p class="text-lg text-gray-600 mb-8">
                        Unfortunately, your distributor application has been rejected. 
                        Please contact support for more information or to appeal this decision.
                    </p>
                </div>
                <div v-else>
                    <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="h-10 w-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Application Status Unknown</h2>
                    <p class="text-lg text-gray-600 mb-8">
                        We could not determine the status of your application. Please contact support.
                    </p>
                </div>
                <Link href="/" class="inline-block bg-blue-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-blue-700 transition">
                    Return Home
                </Link>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
</script>
