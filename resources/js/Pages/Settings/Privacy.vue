<template>
    <MainLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-ink">Privacy Settings</h1>
                <p class="text-ink-soft mt-2">Manage your privacy preferences</p>
            </div>

            <div class="bg-white rounded-card shadow-md p-6 space-y-6">
                <!-- Coming Soon Notice -->
                <div class="bg-brand-tint border border-brand-soft rounded-control p-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-brand mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-brand-dark">Coming Soon</p>
                            <p class="text-sm text-brand-dark mt-1">
                                Privacy settings are currently under development. 
                                Check back later for options to manage your data and preferences.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Placeholder Settings -->
                <div class="space-y-4 opacity-50">
                    <div class="flex items-center justify-between py-4 border-b">
                        <div>
                            <p class="font-semibold text-ink">Email Notifications</p>
                            <p class="text-sm text-ink-soft">Receive order updates and promotions</p>
                        </div>
                        <button class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-not-allowed rounded-full bg-line" disabled>
                            <span class="translate-x-0 inline-block h-5 w-5 rounded-full bg-white shadow transform transition mt-0.5 ml-0.5"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-4 border-b">
                        <div>
                            <p class="font-semibold text-ink">SMS Notifications</p>
                            <p class="text-sm text-ink-soft">Receive delivery updates via text</p>
                        </div>
                        <button class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-not-allowed rounded-full bg-brand" disabled>
                            <span class="translate-x-5 inline-block h-5 w-5 rounded-full bg-white shadow transform transition mt-0.5"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-4">
                        <div>
                            <p class="font-semibold text-ink">Data Sharing</p>
                            <p class="text-sm text-ink-soft">Allow sharing anonymous usage data</p>
                        </div>
                        <button class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-not-allowed rounded-full bg-line" disabled>
                            <span class="translate-x-0 inline-block h-5 w-5 rounded-full bg-white shadow transform transition mt-0.5 ml-0.5"></span>
                        </button>
                    </div>
                </div>

                <a href="/settings" class="block text-center text-brand hover:text-brand-dark font-medium mt-6">
                    ← Back to Account Settings
                </a>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
</script>
