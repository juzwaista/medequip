<template>
    <div class="min-h-screen min-h-dvh min-w-0 flex flex-col bg-mist text-ink overflow-x-hidden">
        <FlashMessage />
        <TermsBanner
            v-if="$page.props.auth.user"
            :needs-acceptance="$page.props.needsTermsAcceptance"
            :user-role="$page.props.auth.user?.role || 'customer'"
        />
        <EmailVerificationBanner :needs-terms-acceptance="$page.props.needsTermsAcceptance" />

        <!-- Missing Business Document Banner -->
        <div v-if="$page.props.auth.user?.business_profile && $page.props.auth.user.business_profile.status === 'pending' && !$page.props.auth.user.business_profile.sec_dti_document_path" 
             class="bg-amber-50 text-amber-900 border-b border-amber-200 px-4 py-2 sm:py-3 relative z-40">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-[13px] sm:text-sm">
                        <strong class="font-semibold">Action required:</strong> Complete your business profile to unlock wholesale purchasing.
                    </p>
                </div>
                <Link href="/business-account/status" class="shrink-0 inline-flex h-9 items-center rounded-control bg-amber-700 px-3.5 text-sm font-medium text-white transition-colors hover:bg-amber-800">
                    Upload document
                </Link>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white border-b border-line sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-w-0">
                <!-- Phones: logo and actions on the first row, search on its own row below. -->
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-x-4 lg:gap-x-8 gap-y-3 py-3 min-w-0">
                    <Link href="/products" class="col-start-1 row-start-1 flex items-center flex-shrink-0 rounded-control focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" aria-label="MedEquip home">
                        <BrandLogo :size="34" :mark-only-on-phones="!!$page.props.auth.user" />
                    </Link>

                    <ProductSearch class="min-w-0 col-span-3 row-start-2 md:col-span-1 md:col-start-2 md:row-start-1 md:mx-auto md:max-w-[640px]" />

                    <!-- Right: notifications, messages, cart, account -->
                    <div class="col-start-3 row-start-1 flex items-center gap-0.5 sm:gap-2 shrink-0">
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
                            <Link href="/cart" class="p-2.5 text-ink-soft hover:text-brand hover:bg-mist rounded-control transition-colors relative block focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand" title="Cart" :aria-label="cartCount > 0 ? `Cart, ${cartCount} items` : 'Cart'">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>

                                <span v-if="cartCount > 0" class="absolute top-0.5 right-0.5 bg-ink text-white text-[11px] font-semibold rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center leading-none tabular-nums">
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
                                    class="absolute right-0 top-full mt-2 w-72 bg-white shadow-lg border border-line rounded-card z-[100] flex flex-col overflow-hidden pointer-events-none"
                                >
                                    <div class="px-4 py-3 border-b border-line bg-mist">
                                        <h2 class="text-sm font-semibold text-ink">Cart preview</h2>
                                    </div>
                                    <div class="divide-y divide-line max-h-64 overflow-hidden">
                                        <div v-for="item in cartPreviewItems" :key="item.id" class="p-3 flex items-center gap-3">
                                            <img v-if="item.image_url" :src="item.image_url" class="w-10 h-10 rounded-control object-cover border border-line" />
                                            <div v-else class="w-10 h-10 rounded-control bg-mist flex items-center justify-center">
                                                <svg class="w-5 h-5 text-ink-faint/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0V17a2 2 0 01-2 2H6a2 2 0 01-2-2v-3z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-ink truncate">{{ item.name }}</p>
                                                <div class="flex items-center justify-between gap-2 mt-0.5">
                                                    <span class="text-xs text-ink-soft line-clamp-1">
                                                        {{ item.variation_name || '' }}
                                                    </span>
                                                    <p class="text-xs text-ink-soft whitespace-nowrap tabular-nums">₱{{ Number(item.price).toLocaleString() }} &times; {{ item.quantity }}</p>
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
                            :csrf-token="$page.props.csrf_token"
                        />
                        <div v-else class="flex items-center gap-1 sm:gap-2">
                            <BaseButton href="/login" variant="ghost" class="hidden sm:inline-flex">Log in</BaseButton>
                            <BaseButton href="/register">Sign up</BaseButton>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content — pb-20 so mobile bottom nav doesn't overlap content -->
        <main class="flex-1 flex flex-col min-w-0 pb-20 md:pb-0">
            <transition
                name="page"
                mode="out-in"
                appear
            >
                <div :key="$page.component" class="flex-1 flex flex-col min-w-0">
                    <slot />
                </div>
            </transition>
        </main>

        <!-- Footer (desktop only gets full footer) -->
        <footer class="hidden md:block bg-ink text-white/70 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <BrandLogo :size="32" light class="mb-3" />
                        <p class="text-sm leading-relaxed">
                            Your trusted medical equipment and supplies marketplace in Cavite.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4 text-sm">Quick links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/about" class="hover:text-white transition-colors">About us</Link></li>
                            <li><Link href="/contact" class="hover:text-white transition-colors">Contact</Link></li>
                            <li><Link href="/help" class="hover:text-white transition-colors">Help center</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4 text-sm">Categories</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/category/medical-equipment" class="hover:text-white transition-colors">Medical equipment</Link></li>
                            <li><Link href="/category/surgical-instruments" class="hover:text-white transition-colors">Surgical instruments</Link></li>
                            <li><Link href="/category/personal-protective-equipment" class="hover:text-white transition-colors">PPE</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4 text-sm">Contact</h4>
                        <p class="text-sm">Cavite, Philippines</p>
                        <p class="text-sm mt-1">contact@medequip.shop</p>
                    </div>
                </div>
                <div class="border-t border-white/10 mt-8 pt-8 flex flex-col sm:flex-row justify-between items-center gap-2 text-sm text-white/50">
                    <p>&copy; 2026 MedEquip Platform. All rights reserved.</p>
                    <div class="flex gap-4">
                        <Link href="/privacy" class="hover:text-white transition-colors">Privacy policy</Link>
                        <Link href="/help" class="hover:text-white transition-colors">Help</Link>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Mobile Bottom Navigation Bar -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-line"
             style="padding-bottom: env(safe-area-inset-bottom, 0px)">
            <div class="grid grid-cols-5 h-16">
                <!-- Browse -->
                <Link href="/products"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="$page.url.startsWith('/products') || $page.url.startsWith('/seller') ? 'text-brand' : 'text-ink-faint hover:text-ink'"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span class="text-[11px] font-medium leading-tight text-center">Browse</span>
                </Link>

                <!-- Messages -->
                <Link
                    :href="messagesNavHref"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="messagesNavActive ? 'text-brand' : 'text-ink-faint hover:text-ink'"
                >
                    <div class="relative">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span
                            v-if="unreadChatMessages > 0"
                            class="absolute -top-1.5 -right-1.5 bg-brand text-white text-[10px] font-semibold rounded-full min-w-[1rem] h-4 px-0.5 flex items-center justify-center leading-none"
                        >
                            {{ unreadChatMessages > 9 ? '9+' : unreadChatMessages }}
                        </span>
                    </div>
                    <span class="text-[11px] font-medium leading-tight text-center">Msgs</span>
                </Link>

                <!-- Cart -->
                <Link href="/cart"
                    class="flex flex-col items-center justify-center gap-0.5 relative transition-colors px-0.5"
                    :class="$page.url.startsWith('/cart') ? 'text-brand' : 'text-ink-faint hover:text-ink'"
                >
                    <div class="relative">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 bg-brand text-white text-[10px] font-semibold rounded-full h-4 w-4 flex items-center justify-center">
                            {{ cartCount > 9 ? '9+' : cartCount }}
                        </span>
                    </div>
                    <span class="text-[11px] font-medium leading-tight text-center">Cart</span>
                </Link>

                <!-- Orders -->
                <Link :href="ordersUrl"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="$page.url.startsWith('/orders') || $page.url.startsWith('/my-orders') || $page.url.startsWith('/owner/orders') ? 'text-brand' : 'text-ink-faint hover:text-ink'"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="text-[11px] font-medium leading-tight text-center">Orders</span>
                </Link>

                <!-- Account or Login -->
                <Link
                    :href="$page.props.auth.user ? '/profile' : '/login'"
                    class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                    :class="$page.url.startsWith('/profile') || $page.url.startsWith('/login') ? 'text-brand' : 'text-ink-faint hover:text-ink'"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-[11px] font-medium leading-tight text-center">{{ $page.props.auth.user ? 'Me' : 'Login' }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import TermsBanner from '@/Components/TermsBanner.vue';
import EmailVerificationBanner from '@/Components/EmailVerificationBanner.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import MessagesHeaderLink from '@/Components/MessagesHeaderLink.vue';
import ProductSearch from '@/Components/ProductSearch.vue';
import BrandLogo from '@/Components/ui/BrandLogo.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
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

<style>
/* Page transition: a short fade only. Sliding every page up on navigation was decoration, not feedback. */
.page-enter-active,
.page-leave-active {
    transition: opacity 0.15s ease-in-out;
}
.page-enter-from,
.page-leave-to {
    opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
    .page-enter-active,
    .page-leave-active {
        transition: none;
    }
}
</style>
