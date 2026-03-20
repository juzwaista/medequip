<template>
    <div class="flex h-screen bg-gray-50 font-sans overflow-hidden">
        <FlashMessage />

        <!-- Mobile Sidebar Backdrop -->
        <transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="mobileSidebarOpen" 
                class="fixed inset-0 bg-gray-900/80 z-40 lg:hidden" 
                @click="mobileSidebarOpen = false"
            ></div>
        </transition>

        <!-- Sidebar -->
        <aside 
            :class="[
                'fixed inset-y-0 left-0 bg-gray-900 border-r border-gray-800 w-64 flex flex-col z-50 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0',
                mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Sidebar Header / Logo -->
            <div class="h-16 flex items-center px-4 bg-gray-950/50 border-b border-gray-800 shrink-0 justify-between lg:justify-center">
                <a href="/owner/dashboard" class="flex items-center space-x-3 group w-full">
                    <div class="h-8 w-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-md">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="flex flex-col truncate">
                        <span class="text-lg font-bold text-white tracking-wide">
                            MedEquip
                        </span>
                        <span class="text-[10px] uppercase tracking-widest text-blue-400 font-medium">
                            {{ isOwner ? 'Owner Portal' : 'Staff Portal' }}
                        </span>
                    </div>
                </a>
                <button @click="mobileSidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <Link 
                    href="/owner/dashboard" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/dashboard') && !isActive('/owner/dashboard/') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h18M3 6h18M3 18h18" v-if="false"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </Link>

                <div class="pt-4 pb-2 px-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Operations</p>
                </div>

                <Link 
                    href="/owner/pos" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors', isActive('/owner/pos') ? 'bg-indigo-600 text-white shadow' : 'bg-gray-800/50 text-indigo-400 hover:text-white hover:bg-indigo-600 hover:shadow']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Start POS
                </Link>

                <Link 
                    href="/owner/inventory" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors mt-1', isActive('/owner/inventory') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Inventory
                </Link>

                <Link 
                    href="/owner/orders" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/orders') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Orders
                </Link>

                <template v-if="isOwner">
                    <div class="pt-4 pb-2 px-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Business Management</p>
                    </div>

                    <Link 
                        href="/owner/sales" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/sales') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Financial Reports
                    </Link>

                    <Link 
                        href="/owner/staff" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/staff') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Staff Management
                    </Link>
                    
                    <Link 
                        href="/owner/profile/edit" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/profile/edit') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Business Profile
                    </Link>
                </template>
            </nav>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden bg-gray-50">
            <!-- Topbar Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <!-- Optional Breadcrumbs can go here -->
                </div>
                
                <div class="flex items-center">
                    <AccountMenu 
                        v-if="$page.props.auth.user"
                        :userName="$page.props.auth.user.name"
                        :userEmail="$page.props.auth.user.email"
                        :userRole="$page.props.auth.user.role || 'distributor'"
                        :csrfToken="csrfToken"
                    />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div v-if="$slots.header" class="bg-white shadow-sm px-4 py-6 sm:px-6 lg:px-8 shrink-0">
                    <slot name="header" />
                </div>
                <!-- Main page body -->
                <div class="p-4 sm:p-6 lg:p-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();
const mobileSidebarOpen = ref(false);

const csrfToken = computed(() => {
    return page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content;
});

// Fixed bug: properly compute isOwner based on user role 
const isOwner = computed(() => {
    return page.props.auth.user?.role === 'distributor';
});

const isShopInternal = computed(() => {
    const role = page.props.auth.user?.role;
    return role === 'distributor' || role === 'staff';
});

const isActive = (path) => {
    return window.location.pathname.startsWith(path);
};
</script>
