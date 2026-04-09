<template>
    <Head :title="status === 'rejected' ? 'Application Rejected' : 'Application Pending'" />
    <OnboardingLayout :title="status === 'rejected' ? 'Application Rejected' : 'Application Under Review'">
        <div class="max-w-2xl mx-auto py-16 px-4">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-10 text-center relative overflow-hidden">
                <!-- Background Icon -->
                <div class="absolute -top-10 -right-10 text-blue-50 opacity-50 pointer-events-none">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                <div class="relative z-10">
                    <!-- Pending state -->
                    <template v-if="status === 'pending'">
                        <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-amber-100 shadow-inner">
                            <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 mb-4">Portal Locked</h1>
                        
                        <p class="text-lg text-gray-600 mb-8 max-w-lg mx-auto leading-relaxed">
                            Your documents are currently under review by the MedEquip Compliance Team. Please allow <span class="font-bold text-gray-900">1-2 business days</span>.
                        </p>
                    </template>

                    <!-- Rejected state -->
                    <template v-else-if="status === 'rejected'">
                        <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-red-100 shadow-inner">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Application Rejected</h1>
                        
                        <div class="mb-6 flex flex-col items-center">
                            <span class="text-xs font-bold uppercase tracking-widest text-red-600 mb-1">Attempt {{ distributor.rejection_count }} of 3</span>
                            <div class="w-32 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 transition-all duration-500" :style="{ width: (distributor.rejection_count / 3 * 100) + '%' }"></div>
                            </div>
                        </div>

                        <p v-if="!distributor.is_suspended" class="text-lg text-gray-600 mb-6 max-w-lg mx-auto leading-relaxed">
                            Unfortunately, your distributor application was not approved. You have <span class="font-bold">{{ 3 - distributor.rejection_count }}</span> attempts remaining.
                        </p>
                        <p v-else class="text-lg text-red-600 font-bold mb-6 max-w-lg mx-auto leading-relaxed">
                            Your account has been suspended due to 3 failed application attempts.
                        </p>

                        <div v-if="distributor?.rejection_reason" class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-left">
                            <p class="text-xs font-bold text-red-800 uppercase tracking-widest mb-1">Reason for REJECTION</p>
                            <p class="text-sm font-medium text-red-700">{{ distributor.rejection_reason }}</p>
                        </div>

                        <div v-if="distributor.is_suspended" class="bg-gray-900 border border-gray-700 rounded-xl p-4 mb-6 text-left shadow-2xl">
                            <p class="text-xs font-bold text-amber-400 uppercase tracking-widest mb-1">Suspension Notice</p>
                            <p class="text-sm font-medium text-gray-300">{{ distributor.suspension_reason || 'Account suspended after maximum rejection attempts reached.' }}</p>
                        </div>
                    </template>

                    <!-- Status Details -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left border border-gray-200">
                         <div class="flex justify-between items-center text-sm mb-3">
                            <span class="text-gray-500 font-semibold">Company</span>
                            <span class="text-gray-900 font-bold">{{ distributor?.company_name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm mb-3">
                            <span class="text-gray-500 font-semibold">Submitted</span>
                            <span class="text-gray-900 font-bold">{{ formatDate(distributor?.created_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-semibold">Status</span>
                            <span :class="[
                                'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide',
                                status === 'pending'
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-red-100 text-red-800'
                            ]">{{ distributor.is_suspended ? 'SUSPENDED' : status }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button v-if="status === 'rejected' && !distributor.is_suspended"
                            @click="goResubmit"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition">
                            Re-submit Application
                        </button>

                        <div v-else-if="distributor.is_suspended" class="text-center">
                             <a href="mailto:support@medequip.ph" class="text-blue-600 hover:underline font-bold text-sm">Contact Support for Verification</a>
                        </div>

                        <Link v-if="status === 'pending'" href="/products"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition">
                            Browse Products
                        </Link>
                        
                        <button @click="logout" class="w-full text-gray-500 hover:text-gray-900 font-bold py-3 px-4 transition">
                            Sign Out
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </OnboardingLayout>
</template>

<script setup>
import { Head, usePage, router, Link } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';

const props = defineProps({
    distributor: Object,
    status: { type: String, default: 'pending' },
});

const page   = usePage();

const logout = () => router.post('/logout');

// Use Inertia router so it goes through the SPA stack and the middleware
// can distinguish the allowed 'distributors.create' route
const goResubmit = () => router.visit('/owner/distributor/create');

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
};
</script>
