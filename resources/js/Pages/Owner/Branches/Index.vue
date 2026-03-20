<template>
    <OwnerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Branch Management</h1>
                    <p class="text-gray-600 mt-2">Manage your pharmacy branches and locations</p>
                </div>
                <Link 
                    href="/owner/dashboard"
                    class="text-blue-600 hover:text-blue-700 font-medium"
                >
                    ← Back to Dashboard
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6">
                    <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Coming Soon</h2>
                <p class="text-gray-600 mb-8 max-w-lg mx-auto">
                    We're building a comprehensive branch management system. Soon you'll be able to add multiple locations, assign inventory to specific branches, and track sales performance by location.
                </p>
                
                <div class="border-t border-gray-200 pt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-bold text-gray-900 mb-2">Multi-Location Support</h4>
                            <p class="text-sm text-gray-600">Add and manage unlimited pharmacy branches across different regions.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-bold text-gray-900 mb-2">Inventory Sync</h4>
                            <p class="text-sm text-gray-600">Transfer stock between branches and track real-time levels.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-bold text-gray-900 mb-2">Staff Management</h4>
                            <p class="text-sm text-gray-600">Assign managers and staff permissions per branch.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
</script>
