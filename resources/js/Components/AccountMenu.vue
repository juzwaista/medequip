<template>
    <div class="relative" ref="menuRef">
        <button 
            @click="isOpen = !isOpen"
            class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition"
        >
            <!-- User Avatar -->
            <div class="h-8 w-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                {{ userInitials }}
            </div>
            <span class="text-sm font-medium text-gray-700 hidden sm:block">{{ userName }}</span>
            <svg 
                :class="['h-4 w-4 text-gray-500 transition-transform', isOpen ? 'rotate-180' : '']" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div 
                v-show="isOpen"
                class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
            >
                <!-- User Info -->
                <div class="px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900">{{ userName }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ userEmail }}</p>
                </div>

                <!-- Menu Items -->
                <div class="py-1">
                    <a 
                        v-if="!isDistributor"
                        href="/my-orders"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        My Orders
                    </a>
                    <a 
                        v-if="!isDistributor && showWallet"
                        href="/wallet"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        My Wallet
                    </a>
                    <a 
                        v-if="!isDistributor"
                        href="/addresses"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        My Addresses
                    </a>
                    <a 
                        v-if="!isDistributor"
                        href="/owner/distributor/create"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Become a Distributor
                    </a>
                    <!-- Distributor links: only shown when approved -->
                    <template v-if="isDistributor">
                        <!-- PENDING / REJECTED: show status link only -->
                        <template v-if="isDistributorPending">
                            <a
                                href="/owner/distributor/pending"
                                class="flex items-center px-4 py-2 text-sm text-amber-600 hover:bg-amber-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Application Pending
                            </a>
                            <a 
                                v-if="showWallet"
                                href="/wallet"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                My Wallet
                            </a>
                        </template>
                        <!-- APPROVED: show full portal links -->
                        <template v-else>
                            <a 
                                :href="dashboardRoute"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Dashboard
                            </a>
                            <a 
                                v-if="!isSuspended"
                                href="/owner/profile/edit"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Business Profile
                            </a>
                            <a 
                                v-if="!isSuspended"
                                href="/owner/inventory"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                Inventory Management
                            </a>
                            <a 
                                href="/owner/orders"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Orders
                            </a>
                            <a 
                                v-if="showWallet"
                                href="/wallet"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                My Wallet
                            </a>
                        </template>
                    </template>
                    <a 
                        href="/settings"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Account Settings
                    </a>
                    <a 
                        href="/privacy"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Privacy Settings
                    </a>
                </div>

                <!-- Logout -->
                <div class="py-1">
                    <button 
                        @click="logout"
                        class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                    >
                        <svg class="h-5 w-5 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const $page = usePage();

const props = defineProps({
    userName: String,
    userEmail: String,
    userRole: {
        type: String,
        default: 'customer'
    },
    csrfToken: String,
    showWallet: {
        type: Boolean,
        default: false,
    },
});

const isOpen = ref(false);
const menuRef = ref(null);

const userInitials = computed(() => {
    if (!props.userName) return '?';
    const parts = props.userName.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return props.userName.substring(0, 2).toUpperCase();
});

const isDistributor = computed(() => {
    return props.userRole === 'distributor' || props.userRole === 'admin';
});

// Distributor with pending/rejected status should not access portal links
const distributorStatus = computed(() => $page.props.auth?.user?.distributor_status ?? null);
const isDistributorPending = computed(() =>
    props.userRole === 'distributor' &&
    (distributorStatus.value === 'pending' || distributorStatus.value === 'rejected')
);

const isSuspended = computed(() => $page.props.auth?.user?.is_suspended ?? false);

const dashboardRoute = computed(() => {
    switch (props.userRole) {
        case 'admin':
        case 'super_admin':
            return '/admin/dashboard';
        case 'distributor':
            return '/owner/dashboard';
        case 'courier':
            return '/courier/dashboard';
        default:
            return '/dashboard';
    }
});

const logout = () => {
    router.post('/logout');
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (menuRef.value && !menuRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
