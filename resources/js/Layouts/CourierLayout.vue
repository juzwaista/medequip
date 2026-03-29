<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <TermsBanner
            v-if="$page.props.auth.user"
            :needs-acceptance="$page.props.needsTermsAcceptance"
            :user-role="$page.props.auth.user?.role || 'courier'"
        />
        <!-- Top Navigation -->
        <nav class="bg-blue-600 border-b border-blue-700 pb-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <img :src="'/images/logo.png'" alt="MedEquip" class="h-24 w-auto scale-125 object-contain">
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 text-white">
                        <span class="text-sm font-medium">{{ $page.props.auth.user.name }}</span>
                        <div class="bg-blue-500 rounded-full h-8 w-8 flex items-center justify-center font-bold relative">
                            {{ $page.props.auth.user.name.charAt(0) }}
                            <span v-if="$page.props.courier?.status === 'active'" class="absolute -bottom-1 -right-1 block h-3 w-3 rounded-full bg-green-400 border-2 border-blue-600"></span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Layout -->
        <main class="-mt-20 flex-1 flex flex-col items-center w-full px-4 sm:px-6 lg:px-8 pb-24">
            <div class="w-full max-w-lg mx-auto bg-white rounded-xl shadow-xl overflow-hidden min-h-[75vh]">
                <slot />
            </div>
        </main>

        <!-- Bottom Mobile Navigation Bar -->
        <div class="fixed bottom-0 w-full bg-white border-t border-gray-200 safe-area-bottom z-50">
            <div class="flex justify-around items-center h-16 max-w-lg mx-auto">
                <Link href="/courier/dashboard"
                    class="flex flex-col items-center justify-center w-full h-full text-center hover:bg-gray-50 transition"
                    :class="$page.url.startsWith('/courier/dashboard') ? 'text-blue-600' : 'text-gray-500'">
                    <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-xs font-semibold">Jobs</span>
                </Link>
                <Link href="/wallet"
                    class="flex flex-col items-center justify-center w-full h-full text-center hover:bg-gray-50 transition"
                    :class="$page.url.startsWith('/wallet') ? 'text-blue-600' : 'text-gray-500'">
                    <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="text-xs font-semibold">Earnings</span>
                </Link>
                <Link href="/settings"
                    class="flex flex-col items-center justify-center w-full h-full text-center hover:bg-gray-50 transition"
                    :class="$page.url.startsWith('/settings') || $page.url.startsWith('/profile') ? 'text-blue-600' : 'text-gray-500'">
                    <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs font-semibold">Account</span>
                </Link>
                <button
                    @click="logout"
                    class="flex flex-col items-center justify-center w-full h-full text-center text-gray-500 hover:bg-gray-50 transition hover:text-red-500">
                    <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="text-xs font-semibold">Sign Out</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import TermsBanner from '@/Components/TermsBanner.vue';

const logout = () => {
    router.post('/logout');
};
</script>

<style>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
