<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <FlashMessage />
        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <a href="/products" class="flex items-center group">
                        <img :src="'/images/logo.png'" alt="MedEquip" class="h-24 w-auto scale-125 object-contain">
                    </a>

                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <Link v-if="$page.props.auth.user && $page.props.auth.user.role !== 'staff'" href="/wallet" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition relative" title="My Wallet">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </Link>
                        
                        <Link href="/cart" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition relative" title="Cart">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-if="cartCount > 0" class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                                {{ cartCount }}
                            </span>
                        </Link>
                        
                        <AccountMenu 
                            v-if="$page.props.auth.user"
                            :userName="$page.props.auth.user.name"
                            :userEmail="$page.props.auth.user.email"
                            :userRole="$page.props.auth.user.role || 'customer'"
                            :csrfToken="csrfToken"
                        />
                        <div v-else class="flex items-center space-x-3">
                            <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium transition">
                                Login
                            </a>
                            <a href="/register" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                Register
                            </a>
                        </div>
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
                        <h4 class="text-white font-semibold mb-4">Categories</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition">Medical Equipment</a></li>
                            <li><a href="#" class="hover:text-white transition">Surgical Instruments</a></li>
                            <li><a href="#" class="hover:text-white transition">PPE</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Contact</h4>
                        <p class="text-sm text-gray-400">Cavite, Philippines</p>
                        <p class="text-sm text-gray-400">support@medequip.com</p>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                    <p>&copy; 2026 MedEquip Platform. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <!-- Global Flash Notifications (single source) -->
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();

// Get CSRF token from meta tag if not in props
const csrfToken = computed(() => {
    return page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content;
});
const cartCount = ref(0);

const updateCartCount = async () => {
    try {
        const response = await fetch('/cart/count');
        const data = await response.json();
        cartCount.value = data.count || 0;
    } catch (error) {
        console.error('Failed to fetch cart count', error);
    }
};

onMounted(() => {
    updateCartCount();
    // Update cart count every 2 seconds (or on page navigation)
    setInterval(updateCartCount, 2000);
});

const getDashboardRoute = () => {
    const role = page.props.auth?.user?.role || 'customer';
    switch (role) {
        case 'admin':
            return '/admin/dashboard';
        case 'distributor':
            return '/owner/dashboard';
        default:
            return '/dashboard';
    }
};
</script>

