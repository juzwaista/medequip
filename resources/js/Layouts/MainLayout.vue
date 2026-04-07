<template>
    <div class="min-h-screen min-h-dvh min-w-0 flex flex-col bg-gradient-to-br from-slate-50 to-gray-100 overflow-x-hidden">
        <FlashMessage />
        <TermsBanner
            v-if="$page.props.auth.user"
            :needs-acceptance="$page.props.needsTermsAcceptance"
            :user-role="$page.props.auth.user?.role || 'customer'"
        />
        <EmailVerificationBanner :needs-terms-acceptance="$page.props.needsTermsAcceptance" />

        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md shadow-md sticky top-0 z-50 border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 min-w-0">
                <div class="flex justify-between items-center gap-2 h-16 sm:h-20 min-w-0">
                    <!-- Logo -->
                    <!-- <a href="/products" class="flex items-center flex-shrink-0 group">
                        <img :src="'/images/logo.png'" alt="MedEquip" class="h-28 w-auto object-contain transition-transform duration-500 group-hover:scale-105 drop-shadow-sm">
                    </a> -->

                    <a href="/products" class="flex items-center flex-shrink-0">
                        <img :src="'/images/logo.png'" 
                            class="h-20 sm:h-16 md:h-24 lg:h-28 w-auto object-contain transition-transform duration-500 drop-shadow-sm">
                    </a>

                    <!-- Nav Links Removed -->

                    <!-- Right: notifications, messages, cart, account -->
                    <div class="flex items-center gap-0.5 sm:gap-2 shrink-0">
                        <NotificationBell
                            v-if="$page.props.auth.user && $page.props.auth.user.email_verified_at"
                            :count="unreadNotifications"
                        />

                        <MessagesHeaderLink
                            v-if="$page.props.auth.user && $page.props.auth.user.email_verified_at"
                            :href="messagesHref"
                            :count="unreadChatMessages"
                        />

                        <div class="relative" @mouseenter="showCartHover" @mouseleave="hideCartHover">
                            <Link href="/cart" class="p-3 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition relative block" title="Cart">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>

                                <span v-if="cartCount > 0" class="absolute top-1 right-1 bg-blue-600 text-white text-[11px] font-black rounded-full h-5 w-5 flex items-center justify-center leading-none">
                                    {{ cartCount > 9 ? '9+' : cartCount }}
                                </span>
                            </Link>

                            <transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div v-if="cartHoverOpen && cartPreviewItems.length > 0" 
                                    class="absolute right-0 top-full mt-2 w-72 bg-white shadow-xl ring-1 ring-black/5 rounded-2xl z-[100] flex flex-col overflow-hidden pointer-events-none"
                                >
                                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/80">
                                        <h2 class="text-xs font-bold tracking-tight text-gray-900 uppercase">Cart Preview</h2>
                                    </div>
                                    <div class="divide-y divide-gray-100 max-h-64 overflow-hidden">
                                        <div v-for="item in cartPreviewItems" :key="item.id" class="p-3 flex items-center gap-3">
                                            <img v-if="item.image_url" :src="item.image_url" class="w-10 h-10 rounded-lg object-cover border border-gray-100" />
                                            <div v-else class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0V17a2 2 0 01-2 2H6a2 2 0 01-2-2v-3z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-gray-900 truncate">{{ item.name }}</p>
                                                <div class="flex items-center justify-between mt-0.5">
                                                    <span class="text-[10px] text-gray-500 line-clamp-1">
                                                        {{ item.variation_name || '' }}
                                                    </span>
                                                    <p class="text-[10px] font-bold text-gray-700 whitespace-nowrap">₱{{ Number(item.price).toLocaleString() }} &times; {{ item.quantity }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <AccountMenu
                            v-if="$page.props.auth.user"
                            :userName="$page.props.auth.user.name"
                            :userEmail="$page.props.auth.user.email"
                            :userRole="$page.props.auth.user.role || 'customer'"
                            :csrfToken="csrfToken"
                            :show-wallet="Boolean($page.props.auth.user.email_verified_at && $page.props.auth.user.role !== 'staff')"
                        />
                        <!-- <div v-else class="flex items-center gap-2">
                            <a href="/login" 
                            class="text-sm sm:text-lg text-gray-600 hover:text-blue-600 font-medium transition px-2 py-1 sm:px-3 sm:py-2 rounded-lg hover:bg-gray-50">
                                Login
                            </a>
                            <a href="/register" 
                            class="text-sm sm:text-lg px-2 py-1 sm:px-4 sm:py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-semibold shadow-sm">
                                Sign Up
                            </a>
                        </div> -->
                        <div v-else class="flex items-center gap-2">
                            <!-- Login Button -->
                           
                            <a href="/login" 
                            class="text-base sm:text-base text-gray-600 font-medium px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-md 
                                    bg-white hover:bg-gradient-to-r hover:from-blue-400 hover:to-blue-600 
                                    hover:text-white shadow-md transition-all duration-300">
                                Login
                            </a>

                            <!-- Sign Up Button -->
                            <a href="/register" 
                            class="text-base sm:text-base px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg 
                                    bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 
                                    text-white font-semibold shadow-lg transition-transform transform hover:scale-105 duration-300">
                                Sign Up
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content — pb-20 so mobile bottom nav doesn't overlap content -->
        <main class="flex-1 flex flex-col min-w-0 pb-20 md:pb-0">
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
            <div class="grid grid-cols-5 h-16">
                <!-- Browse -->
                <Link href="/products"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="$page.url.startsWith('/products') || $page.url.startsWith('/seller') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span class="text-[9px] sm:text-[10px] font-semibold leading-tight text-center">Browse</span>
                </Link>

                <!-- Messages -->
                <Link
                    :href="messagesNavHref"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="messagesNavActive ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <div class="relative">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span
                            v-if="unreadChatMessages > 0"
                            class="absolute -top-1.5 -right-1.5 bg-blue-600 text-white text-[9px] font-black rounded-full min-w-[1rem] h-4 px-0.5 flex items-center justify-center leading-none"
                        >
                            {{ unreadChatMessages > 9 ? '9+' : unreadChatMessages }}
                        </span>
                    </div>
                    <span class="text-[9px] sm:text-[10px] font-semibold leading-tight text-center">Msgs</span>
                </Link>

                <!-- Cart -->
                <Link href="/cart"
                    class="flex flex-col items-center justify-center gap-0.5 relative transition-colors px-0.5"
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
                    <span class="text-[9px] sm:text-[10px] font-semibold leading-tight text-center">Cart</span>
                </Link>

                <!-- Orders -->
                <Link :href="ordersUrl"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="$page.url.startsWith('/orders') || $page.url.startsWith('/my-orders') || $page.url.startsWith('/owner/orders') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="text-[9px] sm:text-[10px] font-semibold leading-tight text-center">Orders</span>
                </Link>

                <!-- Account or Login -->
                <Link
                    :href="$page.props.auth.user ? '/profile' : '/login'"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="$page.url.startsWith('/profile') || $page.url.startsWith('/login') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-[9px] sm:text-[10px] font-semibold leading-tight text-center">{{ $page.props.auth.user ? 'Me' : 'Login' }}</span>
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
import TermsBanner from '@/Components/TermsBanner.vue';
import EmailVerificationBanner from '@/Components/EmailVerificationBanner.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import MessagesHeaderLink from '@/Components/MessagesHeaderLink.vue';
import { useHeaderNotificationPoll } from '@/composables/useHeaderNotificationPoll.js';

const page = usePage();

const { unreadNotifications, unreadChatMessages } = useHeaderNotificationPoll(page);

const csrfToken = computed(() => {
    return page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content;
});
const cartCount = ref(0);
const cartPreviewItems = ref([]);
const cartHoverOpen = ref(false);

function showCartHover() {
    cartHoverOpen.value = true;
}

function hideCartHover() {
    cartHoverOpen.value = false;
}

const ordersUrl = computed(() => {
    const role = page.props.auth.user?.role;
    if (role === 'distributor' || role === 'staff') {
        return '/owner/orders';
    }
    return '/my-orders';
});

const messagesHref = computed(() => {
    const role = page.props.auth.user?.role;
    if (role === 'distributor' || role === 'staff') {
        return '/owner/messages';
    }
    return '/messages';
});

const messagesNavHref = computed(() => {
    const u = page.props.auth?.user;
    if (!u) {
        return '/login';
    }
    if (!u.email_verified_at) {
        return '/verify-email';
    }
    return messagesHref.value;
});

const messagesNavActive = computed(() => {
    const url = page.url || '';
    return url.startsWith('/messages') || url.startsWith('/owner/messages');
});

const updateCartCount = async () => {
    try {
        const response = await fetch('/cart/count', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();
        cartCount.value = data.count || 0;
        if (data.preview) {
             cartPreviewItems.value = data.preview;
        }
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
