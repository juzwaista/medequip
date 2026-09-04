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
                        
                        <div v-if="inCooldown" class="mb-8 p-5 bg-orange-50 border-2 border-orange-200 rounded-xl">
                            <svg class="w-8 h-8 text-orange-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h2 class="text-lg font-bold text-orange-800 mb-1">Cooldown Active</h2>
                            <p class="text-sm text-orange-700">You must wait before re-applying. Your portal will unlock on:</p>
                            <p class="text-md font-bold text-orange-900 mt-2">{{ cooldownEndsAt }}</p>
                        </div>
                        <p v-else class="text-lg text-gray-600 mb-6 max-w-lg mx-auto leading-relaxed">
                            Unfortunately, your distributor application was not approved. Please review the feedback below and update your documents.
                        </p>

                        <div v-if="distributor?.rejection_reason" class="bg-red-50 border border-red-200 rounded-xl p-5 mb-6 text-left shadow-sm">
                            <p class="text-xs font-bold text-red-800 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Reason for Rejection
                            </p>
                            <p class="text-sm font-medium text-red-800 whitespace-pre-wrap leading-relaxed">{{ distributor.rejection_reason }}</p>
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
                        <button v-if="status === 'rejected' && !inCooldown"
                            @click="goResubmit"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition">
                            Update Documents & Re-apply
                        </button>

                        <div v-else-if="inCooldown" class="text-center w-full">
                             <p class="text-sm text-gray-500 font-medium">Re-application portal will unlock automatically once the cooldown period ends.</p>
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
import { computed } from 'vue';
import { Head, usePage, router, Link } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';

const props = defineProps({
    distributor: Object,
    status: { type: String, default: 'pending' },
});

const inCooldown = computed(() => {
    if (!props.distributor?.application_cooldown_until) return false;
    return new Date(props.distributor.application_cooldown_until) > new Date();
});

const cooldownEndsAt = computed(() => {
    if (!props.distributor?.application_cooldown_until) return '';
    return new Date(props.distributor.application_cooldown_until).toLocaleString('en-US', {
        month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'
    });
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
