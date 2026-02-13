<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <FlashMessage />
        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <a href="/owner/dashboard" class="flex items-center space-x-3 group">
                        <div class="h-10 w-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-md group-hover:shadow-lg transition">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                            MedEquip
                        </span>
                    </a>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex space-x-6">
                        <Link href="/owner/dashboard" class="text-gray-700 hover:text-blue-600 font-medium transition">
                            Dashboard
                        </Link>
                        <Link href="/owner/inventory" class="text-gray-700 hover:text-blue-600 font-medium transition">
                            Inventory
                        </Link>
                        <Link href="/owner/orders" class="text-gray-700 hover:text-blue-600 font-medium transition">
                            Orders
                        </Link>
                    </nav>

                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <AccountMenu 
                            v-if="$page.props.auth.user"
                            :userName="$page.props.auth.user.name"
                            :userEmail="$page.props.auth.user.email"
                            :userRole="$page.props.auth.user.role || 'distributor'"
                            :csrfToken="csrfToken"
                        />
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-300 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-white font-semibold mb-4">MedEquip</h3>
                        <p class="text-sm text-gray-400">
                            Your trusted medical equipment and supplies marketplace in Cavite.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition">About Us</a></li>
                            <li><a href="#" class="hover:text-white transition">Contact</a></li>
                            <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">For Distributors</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/owner/dashboard" class="hover:text-white transition">Dashboard</Link></li>
                            <li><Link href="/owner/inventory" class="hover:text-white transition">Inventory</Link></li>
                            <li><Link href="/owner/orders" class="hover:text-white transition">Orders</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                    <p>&copy; 2026 MedEquip Platform. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();

const csrfToken = computed(() => {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
});
</script>
