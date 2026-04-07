<template>
    <Head title="Invalid Invitation" />
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-2xl sm:rounded-3xl sm:px-10 border border-gray-100 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6 font-black text-red-600">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-gray-900 mb-2">Invitation Error</h2>
                <p class="text-gray-600 mb-8 font-medium">
                    {{ message || 'This invitation link is invalid, expired, or has already been used.' }}
                </p>
                <div class="space-y-4">
                    <Link
                        href="/login"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-black text-white bg-blue-600 hover:bg-blue-700 transition active:scale-95"
                    >
                        Back to Login
                    </Link>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">
                        Need help? contact platform support
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>
