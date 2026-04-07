<template>
    <div class="flex h-[100dvh] min-h-0 bg-gray-50 font-sans overflow-hidden min-w-0">
        <FlashMessage />
        <TermsBanner
            v-if="$page.props.auth.user"
            :needs-acceptance="$page.props.needsTermsAcceptance"
            :user-role="$page.props.auth.user?.role || 'distributor'"
        />

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
                <a href="/owner/dashboard" class="flex items-center gap-3 group w-full">
                    <img :src="'/images/logo.png'" alt="MedEquip" class="h-16 w-auto scale-125 object-contain">
                    <span class="text-[10px] uppercase tracking-widest text-blue-400 font-medium">
                        {{ isOwner ? 'Owner Portal' : 'Staff Portal' }}
                    </span>
                </a>
                <button @click="mobileSidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <template v-if="!isSuspended">
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

                <Link 
                    href="/owner/messages" 
                    :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/messages') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Messages
                </Link>

                <template v-if="isOwner">
                    <div class="pt-4 pb-2 px-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Business Management</p>
                    </div>

                    <Link 
                        href="/owner/insights" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/insights') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Insights
                    </Link>

                    <Link 
                        href="/owner/sales" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/sales') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Financial Reports
                    </Link>

                    <Link 
                        href="/wallet" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors mt-1', isActive('/wallet') ? 'bg-emerald-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Wallet
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
                </template>
                <template v-else>
                    <Link 
                        href="/owner/dashboard" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/dashboard') && !isActive('/owner/dashboard/') ? 'bg-rose-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Suspension Notice
                    </Link>
                    <Link 
                        href="/owner/orders" 
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive('/owner/orders') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800']"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Process Orders
                    </Link>
                </template>
            </nav>

            <!-- Quick actions (fixed at bottom of sidebar) -->
            <div v-if="!isSuspended" class="shrink-0 border-t border-gray-800 bg-gray-950 px-3 py-4 space-y-1">
                <p class="px-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick actions</p>
                <Link
                    href="/owner/inventory/create"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800 transition-colors"
                >
                    <svg class="h-4 w-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add product
                </Link>
                <Link
                    href="/owner/orders?status=pending"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800 transition-colors"
                >
                    <svg class="h-4 w-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    View pending orders
                </Link>
                <Link
                    href="/owner/dashboard#inventory-alerts"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800 transition-colors"
                >
                    <svg class="h-4 w-4 shrink-0 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    View alerts
                </Link>
            </div>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col min-w-0 h-[100dvh] min-h-0 overflow-hidden bg-gray-50">
            <!-- Topbar Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <!-- Optional Breadcrumbs can go here -->
                </div>
                
                <div class="flex items-center gap-1">
                    <NotificationBell :count="unreadNotifications" />
                    <MessagesHeaderLink
                        v-if="$page.props.auth.user?.email_verified_at"
                        href="/owner/messages"
                        :count="unreadChatMessages"
                    />
                    <AccountMenu 
                        v-if="$page.props.auth.user"
                        :userName="$page.props.auth.user.name"
                        :userEmail="$page.props.auth.user.email"
                        :userRole="$page.props.auth.user.role || 'distributor'"
                        :csrfToken="csrfToken"
                        :show-wallet="Boolean($page.props.auth.user.email_verified_at && $page.props.auth.user.role !== 'staff')"
                    />
                </div>
            </header>

            <!-- Page Content -->
            <main
                class="flex-1 flex flex-col min-h-0 pb-20 lg:pb-0"
                :class="isOwnerMessagesThread ? 'overflow-hidden' : 'overflow-y-auto'"
            >
                <div v-if="$slots.header" class="bg-white shadow-sm px-4 py-6 sm:px-6 lg:px-8 shrink-0">
                    <slot name="header" />
                </div>
                <!-- Main page body -->
                <div
                    class="min-w-0 flex flex-col flex-1 min-h-0"
                    :class="isOwnerMessagesThread
                        ? 'overflow-hidden p-0 sm:p-0 lg:p-0'
                        : 'overflow-x-auto p-3 sm:p-6 lg:p-8'"
                >
                    <slot />
                </div>
            </main>
            
            <!-- Mobile Bottom Navigation (Distributor/Staff) -->
            <nav v-if="!isSuspended" class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg"
                 style="padding-bottom: env(safe-area-inset-bottom, 0px)">
                <div class="grid grid-cols-5 h-16">
                    <!-- Dashboard -->
                    <Link href="/owner/dashboard"
                        class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                        :class="isActive('/owner/dashboard') && !isActive('/owner/dashboard/') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="text-[9px] font-semibold leading-tight text-center">Home</span>
                    </Link>

                    <!-- Customer messages -->
                    <Link href="/owner/messages"
                        class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                        :class="isActive('/owner/messages') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
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
                        <span class="text-[9px] font-semibold leading-tight text-center">Msgs</span>
                    </Link>

                    <!-- POS -->
                    <Link href="/owner/pos"
                        class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                        :class="isActive('/owner/pos') ? 'text-indigo-600' : 'text-gray-400'"
                    >
                        <div class="p-1 px-3 rounded-full" :class="isActive('/owner/pos') ? 'bg-indigo-50 border border-indigo-100' : ''">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold leading-tight text-center" :class="isActive('/owner/pos') ? 'text-indigo-600' : ''">POS</span>
                    </Link>

                    <!-- Inventory -->
                    <Link href="/owner/inventory"
                        class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                        :class="isActive('/owner/inventory') ? 'text-blue-600' : 'text-gray-400'"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="text-[9px] font-semibold leading-tight text-center">Stock</span>
                    </Link>

                    <!-- Orders -->
                    <Link href="/owner/orders"
                        class="flex flex-col items-center justify-center gap-0.5 transition-colors px-0.5"
                        :class="isActive('/owner/orders') ? 'text-blue-600' : 'text-gray-400'"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="text-[9px] font-semibold leading-tight text-center">Orders</span>
                    </Link>
                </div>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import TermsBanner from '@/Components/TermsBanner.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import MessagesHeaderLink from '@/Components/MessagesHeaderLink.vue';
import { useHeaderNotificationPoll } from '@/composables/useHeaderNotificationPoll.js';

const page = usePage();

const { unreadNotifications, unreadChatMessages } = useHeaderNotificationPoll(page);
const mobileSidebarOpen = ref(false);

const isSuspended = computed(() => {
    return page.props.auth?.user?.is_suspended ?? false;
});

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

/** Full-viewport chat thread: avoid nested scroll with OwnerLayout main. */
const isOwnerMessagesThread = computed(() => {
    const path = (page.url || '').split('?')[0] || '';
    return /^\/owner\/messages\/\d+/.test(path);
});

const isActive = (path) => {
    return window.location.pathname.startsWith(path);
};
</script>
