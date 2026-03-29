<template>
    <Head :title="title" />
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 flex flex-col">
        <!-- Top bar -->
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/60">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-900 tracking-tight">MedEquip</span>
                    <span class="hidden sm:inline text-gray-300 font-light">|</span>
                    <span class="hidden sm:inline text-sm text-gray-500 font-medium">Distributor Portal Setup</span>
                </div>
                <button @click="router.post('/logout')" class="text-sm text-gray-500 hover:text-gray-800 font-medium transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign Out
                </button>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 flex flex-col items-center justify-start py-8 px-4">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="text-center py-4 text-xs text-gray-400">
            © {{ new Date().getFullYear() }} MedEquip. All documents are securely stored and kept confidential.
        </footer>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Distributor Onboarding' },
});
</script>
