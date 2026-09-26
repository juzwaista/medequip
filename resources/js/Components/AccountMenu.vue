<template>
    <div class="relative" ref="menuRef">
        <button 
            @click="isOpen = !isOpen"
            class="flex items-center space-x-2 px-3 py-2 rounded-control hover:bg-mist transition"
        >
            <!-- User Avatar -->
            <div class="h-8 w-8 bg-gradient-to-br from-brand to-brand rounded-full flex items-center justify-center text-white font-semibold text-sm">
                {{ userInitials }}
            </div>
            <span class="text-sm font-medium text-ink hidden sm:block">{{ userName }}</span>
            <svg 
                :class="['h-4 w-4 text-ink-soft transition-transform', isOpen ? 'rotate-180' : '']" 
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
                class="absolute right-0 mt-2 w-64 bg-white rounded-card shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-line z-50"
            >
                <!-- User Info -->
                <div class="px-4 py-3">
                    <p class="text-sm font-semibold text-ink">{{ userName }}</p>
                    <p class="text-xs text-ink-soft truncate">{{ userEmail }}</p>
                </div>

                <!-- Menu Items -->
                <div class="py-1">
                    <!-- 1. ADMIN MENU SECTION -->
                    <template v-if="isAdmin">
                        <Link 
                            :href="dashboardRoute"
                            class="flex items-center px-4 py-3 text-sm text-brand-dark font-bold hover:bg-brand-tint transition border-b border-brand-soft"
                        >
                            <svg class="h-5 w-5 mr-3 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Admin Dashboard
                        </Link>
                    </template>

                    <!-- 2. BUYER MENU SECTION -->
                    <template v-if="!isAdmin">
                        <div class="px-4 py-2 text-xs font-bold text-ink-faint   bg-mist/50">Buyer Tools</div>
                        
                        <Link href="/my-orders" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                            <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            My Orders
                        </Link>

                        <Link href="/addresses" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                            <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            My Addresses
                        </Link>
                        
                        <Link v-if="!$page.props.auth.user?.business_profile" href="/discount-ids" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                            <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                            {{ $page.props.auth.user?.has_discount_id ? 'My PWD/Senior ID' : 'Apply for Discount' }}
                        </Link>

                        <template v-if="!$page.props.auth.user?.business_profile">
                            <Link href="/business-account/apply" class="flex items-center px-4 py-2 text-sm text-brand-dark font-medium hover:bg-brand-tint transition">
                                <svg class="h-5 w-5 mr-3 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                Apply for Corporate Account
                            </Link>
                        </template>
                        <template v-else>
                            <Link href="/business-account/status" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                                <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                Corporate Account ({{ $page.props.auth.user.business_profile.status }})
                            </Link>
                        </template>
                    </template>

                    <!-- 3. DISTRIBUTOR MENU SECTION -->
                    <template v-if="!isAdmin">
                        <div class="px-4 py-2 mt-1 border-t border-line text-xs font-bold text-ink-faint   bg-mist/50">Distributor Tools</div>
                        
                        <template v-if="!isDistributor">
                            <Link href="/owner/distributor/create" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                                <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                Become a Distributor
                            </Link>
                        </template>

                        <template v-if="isDistributorOnly">
                            <template v-if="isDistributorPending">
                                <Link href="/owner/distributor/pending" class="flex items-center px-4 py-2 text-sm text-amber-600 hover:bg-amber-50 transition">
                                    <svg class="h-5 w-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Application Pending
                                </Link>
                            </template>
                            <template v-else>
                                <Link :href="dashboardRoute" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                                    <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                    Dashboard
                                </Link>
                                <Link v-if="!isSuspended" href="/owner/profile/edit" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                                    <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    Business Profile
                                </Link>
                                <Link href="/owner/inventory" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                                    <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                    Inventory
                                </Link>
                                <Link href="/owner/orders" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                                    <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                    Orders
                                </Link>
                            </template>
                        </template>
                    </template>

                    <!-- Common Settings (Always visible) -->
                    <Link href="/settings" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                        <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Account Settings
                    </Link>
                    <Link href="/privacy" class="flex items-center px-4 py-2 text-sm text-ink hover:bg-mist transition">
                        <svg class="h-5 w-5 mr-3 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Privacy Settings
                    </Link>

                    <!-- Resources -->
                    <div class="px-4 py-2 mt-1 border-t border-line text-xs font-bold text-ink-faint   bg-mist/50">Resources</div>
                    <Link href="/guides/b2b" class="flex items-center px-4 py-2 text-sm text-brand-dark hover:bg-brand-tint transition">
                        <svg class="h-5 w-5 mr-3 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        B2B & Distributor Guide
                    </Link>
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
import { usePage, router, Link } from '@inertiajs/vue3';

const $page = usePage();

const props = defineProps({
    userName: String,
    userEmail: String,
    userRole: {
        type: String,
        default: 'customer'
    },
    csrfToken: String,
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

const isAdmin = computed(() => {
    return props.userRole === 'admin' || props.userRole === 'super_admin';
});

const isDistributorOnly = computed(() => {
    return props.userRole === 'distributor' || props.userRole === 'staff';
});

// For logic that checks if ANY non-customer role is active
const isDistributor = computed(() => {
    return isDistributorOnly.value || isAdmin.value;
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
