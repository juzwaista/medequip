<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex flex-col items-center justify-center p-6">

        <!-- Animated background orbs -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
        </div>

        <div class="relative z-10 w-full max-w-lg">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img :src="'/images/logo.png'" alt="MedEquip" class="h-14 w-auto object-contain mx-auto mb-1 brightness-0 invert">
            </div>

            <!-- Status Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl text-center">

                <!-- Status Icon -->
                <div class="flex justify-center mb-6">
                    <!-- Pending -->
                    <div v-if="status === 'pending'" class="relative">
                        <div class="w-24 h-24 rounded-full bg-amber-500/20 border-2 border-amber-400/50 flex items-center justify-center">
                            <svg class="w-12 h-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="absolute -bottom-1 -right-1 flex h-5 w-5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-5 w-5 bg-amber-500"></span>
                        </span>
                    </div>
                    <!-- Rejected -->
                    <div v-else-if="status === 'rejected'" class="w-24 h-24 rounded-full bg-red-500/20 border-2 border-red-400/50 flex items-center justify-center">
                        <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Title & Subtitle -->
                <template v-if="status === 'pending'">
                    <h1 class="text-2xl font-bold text-white mb-2">Application Under Review</h1>
                    <p class="text-blue-200/80 text-sm leading-relaxed">
                        Your distributor application has been submitted and is currently being reviewed by our team.
                        We'll notify you once a decision has been made.
                    </p>
                </template>
                <template v-else-if="status === 'rejected'">
                    <h1 class="text-2xl font-bold text-white mb-2">Application Rejected</h1>
                    <p class="text-red-200/80 text-sm leading-relaxed">
                        Unfortunately, your distributor application was not approved at this time.
                    </p>
                    <!-- Rejection reason from admin -->
                    <div v-if="distributor?.rejection_reason" class="mt-3 bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-left">
                        <p class="text-xs font-semibold text-red-300/70 uppercase tracking-wider mb-1">Reason from admin</p>
                        <p class="text-sm text-red-200">{{ distributor.rejection_reason }}</p>
                    </div>
                </template>

                <!-- Application Details -->
                <div v-if="distributor" class="mt-6 bg-white/5 rounded-2xl p-4 text-left space-y-3 border border-white/5">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-blue-200/60">Company</span>
                        <span class="text-white font-medium">{{ distributor.company_name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-blue-200/60">Submitted</span>
                        <span class="text-white font-medium">{{ formatDate(distributor.created_at) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-blue-200/60">Status</span>
                        <span :class="[
                            'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide',
                            status === 'pending'
                                ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                                : 'bg-red-500/20 text-red-300 border border-red-500/30'
                        ]">{{ status }}</span>
                    </div>
                </div>

                <!-- What happens next (pending only) -->
                <div v-if="status === 'pending'" class="mt-6 text-left">
                    <p class="text-xs font-semibold text-blue-300/50 uppercase tracking-widest mb-3">What happens next</p>
                    <ul class="space-y-2">
                        <li v-for="step in nextSteps" :key="step" class="flex items-start gap-3 text-sm text-blue-100/60">
                            <svg class="h-4 w-4 text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ step }}
                        </li>
                    </ul>
                </div>

                <!-- Locked notice -->
                <div class="mt-6 flex items-center justify-center gap-2 text-xs text-blue-300/40">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Portal access is locked until your application is approved
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex flex-col gap-3">
                    <!-- Rejected: Re-submit uses Inertia router so middleware allows it -->
                    <button v-if="status === 'rejected'"
                        @click="goResubmit"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 px-6 rounded-xl transition text-sm">
                        Re-submit Application
                    </button>
                    <!-- Pending: browse while waiting -->
                    <a v-if="status === 'pending'" href="/products"
                        class="w-full bg-white/10 hover:bg-white/20 text-white font-medium py-3 px-6 rounded-xl transition text-sm border border-white/10 text-center">
                        Browse Products While You Wait
                    </a>
                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button type="submit" class="w-full text-blue-300/50 hover:text-blue-300 text-sm transition py-2">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-blue-300/30 text-xs mt-6">
                Questions? Contact
                <a href="mailto:support@medequip.com" class="text-blue-300/60 hover:text-blue-300 underline transition">
                    support@medequip.com
                </a>
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    distributor: Object,
    status: { type: String, default: 'pending' },
});

const page   = usePage();
const csrfToken = computed(() =>
    page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content
);

const nextSteps = [
    'Our team will review your submitted documents',
    'You\'ll receive an email notification on approval or rejection',
    'Once approved, you\'ll get full access to your distributor portal',
];

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
