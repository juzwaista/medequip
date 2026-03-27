<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100">
        <FlashMessage />

        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md shadow-md sticky top-0 z-50 border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <a href="/products" class="flex items-center flex-shrink-0 group">
                        <img :src="'/images/logo.png'" alt="MedEquip" class="h-12 w-auto object-contain transition-transform duration-500 group-hover:scale-105 drop-shadow-sm">
                    </a>

                    <!-- Nav Links Removed -->

                    <!-- Right: Wallet, Cart, Account -->
                    <div class="flex items-center gap-1 sm:gap-2">
                        <Link
                            v-if="$page.props.auth.user && $page.props.auth.user.role !== 'staff'"
                            href="/wallet"
                            class="p-2.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition relative"
                            title="My Wallet"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </Link>

                        <Link href="/cart" class="p-2.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition relative" title="Cart">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-if="cartCount > 0" class="absolute top-1 right-1 bg-blue-600 text-white text-[10px] font-black rounded-full h-4 w-4 flex items-center justify-center leading-none">
                                {{ cartCount > 9 ? '9+' : cartCount }}
                            </span>
                        </Link>

                        <AccountMenu
                            v-if="$page.props.auth.user"
                            :userName="$page.props.auth.user.name"
                            :userEmail="$page.props.auth.user.email"
                            :userRole="$page.props.auth.user.role || 'customer'"
                            :csrfToken="csrfToken"
                        />
                        <div v-else class="flex items-center gap-2">
                            <a href="/login" class="text-sm text-gray-600 hover:text-blue-600 font-medium transition px-3 py-2 rounded-lg hover:bg-gray-50">
                                Login
                            </a>
                            <a href="/register" class="text-sm px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-semibold shadow-sm">
                                Sign Up
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content — pb-20 so mobile bottom nav doesn't overlap content -->
        <main class="pb-20 md:pb-0">
            <slot />
        </main>

        <!-- Footer (desktop only gets full footer) -->
        <footer class="hidden md:block bg-gray-900 text-gray-300 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-white font-bold mb-3 text-lg">MedEquip</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">
                            Your trusted medical equipment and supplies marketplace in Cavite.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/about" class="hover:text-white transition">About Us</Link></li>
                            <li><Link href="/contact" class="hover:text-white transition">Contact</Link></li>
                            <li><Link href="/help" class="hover:text-white transition">Help Center</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Categories</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/products?category=medical-equipment" class="hover:text-white transition">Medical Equipment</Link></li>
                            <li><Link href="/products?category=surgical-instruments" class="hover:text-white transition">Surgical Instruments</Link></li>
                            <li><Link href="/products?category=personal-protective-equipment" class="hover:text-white transition">PPE</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Contact</h4>
                        <p class="text-sm text-gray-400">Cavite, Philippines</p>
                        <p class="text-sm text-gray-400 mt-1">support@medequip.com</p>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col sm:flex-row justify-between items-center gap-2 text-sm text-gray-500">
                    <p>&copy; 2026 MedEquip Platform. All rights reserved.</p>
                    <div class="flex gap-4">
                        <Link href="/privacy" class="hover:text-gray-300 transition">Privacy Policy</Link>
                        <Link href="/help" class="hover:text-gray-300 transition">Help</Link>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Mobile Bottom Navigation Bar -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl"
             style="padding-bottom: env(safe-area-inset-bottom, 0px)">
            <div class="grid grid-cols-4 h-16">
                <!-- Browse -->
                <Link href="/products"
                    class="flex flex-col items-center justify-center gap-1 transition-colors"
                    :class="$page.url.startsWith('/products') || $page.url.startsWith('/seller') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span class="text-[10px] font-semibold">Browse</span>
                </Link>

                <!-- Cart -->
                <Link href="/cart"
                    class="flex flex-col items-center justify-center gap-1 relative transition-colors"
                    :class="$page.url.startsWith('/cart') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <div class="relative">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 bg-blue-600 text-white text-[9px] font-black rounded-full h-4 w-4 flex items-center justify-center">
                            {{ cartCount > 9 ? '9+' : cartCount }}
                        </span>
                    </div>
                    <span class="text-[10px] font-semibold">Cart</span>
                </Link>

                <!-- Orders -->
                <Link href="/orders"
                    class="flex flex-col items-center justify-center gap-1 transition-colors"
                    :class="$page.url.startsWith('/orders') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="text-[10px] font-semibold">Orders</span>
                </Link>

                <!-- Account or Login -->
                <Link
                    :href="$page.props.auth.user ? '/profile' : '/login'"
                    class="flex flex-col items-center justify-center gap-1 transition-colors"
                    :class="$page.url.startsWith('/profile') || $page.url.startsWith('/login') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-[10px] font-semibold">{{ $page.props.auth.user ? 'Account' : 'Login' }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();

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
        // silent
    }
};

onMounted(() => {
    updateCartCount();
    // Listen for cart-updated events from Add to Cart buttons
    window.addEventListener('cart-updated', updateCartCount);
    setInterval(updateCartCount, 10000);
});
</script>
