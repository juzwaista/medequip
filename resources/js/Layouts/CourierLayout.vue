<template>
    <div class="min-h-[100dvh] min-w-0 bg-mist flex flex-col overflow-x-hidden">
        <TermsBanner
            v-if="$page.props.auth.user"
            :needs-acceptance="$page.props.needsTermsAcceptance"
            :user-role="$page.props.auth.user?.role || 'courier'"
        />
        <!-- Top Navigation -->
        <nav class="bg-ink shrink-0" aria-label="Courier">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 min-w-0">
                <div class="flex justify-between items-center h-14 sm:h-16 gap-2 min-w-0">
                    <BrandLogo light :size="30" gap="#12262B" />
                    <div class="flex items-center gap-3 text-white">
                        <span class="text-sm font-medium">{{ $page.props.auth.user.name }}</span>
                        <div class="relative flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-sm font-semibold">
                            {{ $page.props.auth.user.name.charAt(0) }}
                            <span v-if="$page.props.courier?.status === 'active'" class="absolute -bottom-0.5 -right-0.5 block h-3 w-3 rounded-full border-2 border-ink bg-brand-soft" title="Active" aria-label="Active"></span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Layout -->
        <main class="flex-1 flex flex-col items-stretch w-full px-3 sm:px-6 lg:px-8 pb-28 sm:pb-24 min-w-0">
            <div class="w-full max-w-lg mx-auto bg-white rounded-card shadow-xl overflow-hidden min-h-[60vh] sm:min-h-[75vh] my-4 sm:my-6">
                <slot />
            </div>
        </main>

        <!-- Bottom Mobile Navigation Bar -->
        <div class="fixed bottom-0 w-full bg-white border-t border-line safe-area-bottom z-50">
            <div class="flex justify-around items-center h-16 max-w-lg mx-auto">
                <Link href="/courier/dashboard"
                    class="flex flex-col items-center justify-center w-full h-full text-center hover:bg-mist transition"
                    :class="$page.url.startsWith('/courier/dashboard') ? 'text-brand' : 'text-ink-soft'">
                    <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-xs font-semibold">Jobs</span>
                </Link>
                <Link href="/settings"
                    class="flex flex-col items-center justify-center w-full h-full text-center hover:bg-mist transition"
                    :class="$page.url.startsWith('/settings') || $page.url.startsWith('/profile') ? 'text-brand' : 'text-ink-soft'">
                    <svg class="h-6 w-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs font-semibold">Account</span>
                </Link>
                <button
                    @click="logout"
                    class="flex flex-col items-center justify-center w-full h-full text-center text-ink-soft hover:bg-mist transition hover:text-red-500">
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
import BrandLogo from '@/Components/ui/BrandLogo.vue';

const logout = () => {
    router.post('/logout');
};
</script>

<style>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
