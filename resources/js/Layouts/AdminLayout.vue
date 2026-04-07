<template>
    <div class="flex h-[100dvh] min-h-0 bg-gray-50 font-sans overflow-hidden">
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
                <a href="/admin/dashboard" class="flex items-center gap-3 group w-full">
                    <img :src="'/images/logo.png'" alt="MedEquip" class="h-12 w-auto scale-125 object-contain">
                    <span class="text-[10px] uppercase tracking-widest text-blue-400 font-medium">Admin Panel</span>
                </a>
                <button @click="mobileSidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <Link 
                    href="/admin/dashboard" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/admin/dashboard') && !isActive('/admin/dashboard/') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </Link>

                <Link
                    href="/admin/reports"
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/admin/reports') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span class="flex-1">Reports hub</span>
                    <span
                        v-if="openReportCount > 0"
                        class="text-[10px] font-black bg-rose-500 text-white rounded-full min-w-[1.25rem] px-1.5 py-0.5 text-center leading-none"
                    >
                        {{ openReportCount > 99 ? '99+' : openReportCount }}
                    </span>
                </Link>

                <div class="pt-4 pb-2 px-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Management</p>
                </div>

                <Link 
                    href="/admin/users" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/admin/users') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="flex-1">Users & Shops</span>
                    <span
                        v-if="pendingVerifications > 0"
                        class="text-[10px] font-black bg-amber-500 text-white rounded-full min-w-[1.25rem] px-1.5 py-0.5 text-center leading-none"
                    >
                        {{ pendingVerifications > 99 ? '99+' : pendingVerifications }}
                    </span>
                </Link>

                <Link 
                    href="/admin/orders" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/admin/orders') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Orders
                </Link>

                <Link
                    href="/admin/products"
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/admin/products') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Products
                </Link>

                <Link 
                    href="/admin/couriers" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/admin/couriers') && !isActive('/admin/couriers/deliveries') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Couriers
                </Link>

                <template v-if="$page.props.auth.user && $page.props.auth.user.role === 'super_admin'">
                    <div class="pt-4 pb-2 px-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Super Admin</p>
                    </div>

                    <Link 
                        href="/superadmin/staff" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/superadmin/staff') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Platform Staff
                    </Link>

                    <Link 
                        href="/superadmin/couriers/deliveries" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/superadmin/couriers/deliveries') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        Global Deliveries
                    </Link>
                    
                    <Link 
                        href="/superadmin/settings" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/superadmin/settings') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </Link>
                </template>
            </nav>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col min-w-0 h-[100dvh] min-h-0 overflow-hidden bg-gray-50">
            <!-- Topbar Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
                
                <div class="flex items-center">
                    <AccountMenu 
                        v-if="$page.props.auth.user"
                        :userName="$page.props.auth.user.name"
                        :userEmail="$page.props.auth.user.email"
                        :userRole="$page.props.auth.user.role"
                        :csrfToken="csrfToken"
                        :show-wallet="Boolean($page.props.auth.user.email_verified_at && $page.props.auth.user.role !== 'staff')"
                    />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div v-if="$slots.header" class="bg-white shadow-sm px-4 py-6 sm:px-6 lg:px-8 shrink-0">
                    <slot name="header" />
                </div>
                <!-- Main page body -->
                <div class="p-3 sm:p-6 lg:p-8 min-w-0 overflow-x-auto">
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

const openReportCount = computed(() => {
    const n = page.props.open_reports_hub_count ?? page.props.open_message_reports_count;
    return Number(n) || 0;
});

const pendingVerifications = computed(() => {
    return Number(page.props.pending_verifications_count) || 0;
});

const csrfToken = computed(() => {
    return page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content;
});

const isActive = (path) => {
    return window.location.pathname.startsWith(path);
};
</script>
