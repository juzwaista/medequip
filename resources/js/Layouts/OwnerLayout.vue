<template>
    <div class="flex h-[100dvh] min-h-0 min-w-0 overflow-hidden bg-mist text-ink">
        <FlashMessage />
        <TermsBanner
            v-if="$page.props.auth.user"
            :needs-acceptance="$page.props.needsTermsAcceptance"
            :user-role="$page.props.auth.user?.role || 'distributor'"
        />

        <PortalSidebar
            :groups="navGroups"
            :portal-label="isOwner ? 'Seller portal' : 'Staff portal'"
            home-href="/owner/dashboard"
            :open="mobileSidebarOpen"
            @close="mobileSidebarOpen = false"
        >
            <template v-if="!isSuspended" #footer>
                <p class="mb-2 px-3 text-xs font-medium text-white/40">Quick actions</p>
                <ul class="space-y-0.5">
                    <li v-for="action in quickActions" :key="action.href">
                        <Link
                            :href="action.href"
                            class="flex items-center gap-2 rounded-control px-3 py-2 text-sm font-medium text-white/75 transition-colors hover:bg-white/5 hover:text-white"
                        >
                            <svg class="h-4 w-4 shrink-0 text-brand-soft" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path v-for="d in portalIcons[action.icon]" :key="d" :d="d" />
                            </svg>
                            {{ action.text }}
                        </Link>
                    </li>
                </ul>
            </template>
        </PortalSidebar>

        <!-- Main workspace -->
        <div class="flex h-[100dvh] min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-white px-4 lg:px-8">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = true" class="rounded-control p-1.5 text-ink-soft hover:text-ink focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand lg:hidden" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
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
                    />
                </div>
            </header>

            <!-- Page content -->
            <main
                class="flex min-h-0 flex-1 flex-col pb-20 lg:pb-0"
                :class="isOwnerMessagesThread ? 'overflow-hidden' : 'overflow-y-auto'"
            >
                <div v-if="$slots.header" class="shrink-0 border-b border-line bg-white px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
                <div
                    class="flex min-h-0 min-w-0 flex-1 flex-col"
                    :class="isOwnerMessagesThread
                        ? 'overflow-hidden p-0 sm:p-0 lg:p-0'
                        : 'overflow-x-auto p-3 sm:p-6 lg:p-8'"
                >
                    <slot />
                </div>
            </main>

            <!-- Phone bottom navigation -->
            <nav
                v-if="!isSuspended"
                class="fixed bottom-0 left-0 right-0 z-40 border-t border-line bg-white lg:hidden"
                style="padding-bottom: env(safe-area-inset-bottom, 0px)"
                aria-label="Quick navigation"
            >
                <div class="grid h-16 grid-cols-5">
                    <Link
                        v-for="tab in bottomTabs"
                        :key="tab.href"
                        :href="tab.href"
                        class="flex flex-col items-center justify-center gap-0.5 px-0.5 text-[11px] font-medium transition-colors"
                        :class="tab.active ? 'text-brand' : 'text-ink-faint hover:text-ink'"
                        :aria-current="tab.active ? 'page' : undefined"
                    >
                        <span class="relative">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path v-for="d in portalIcons[tab.icon]" :key="d" :d="d" />
                            </svg>
                            <span
                                v-if="tab.badge > 0"
                                class="absolute -right-2 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-brand px-0.5 text-[10px] font-semibold leading-none text-white"
                            >
                                {{ tab.badge > 9 ? '9+' : tab.badge }}
                            </span>
                        </span>
                        {{ tab.text }}
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
import PortalSidebar from '@/Components/ui/PortalSidebar.vue';
import { portalIcons } from '@/utils/portalIcons.js';
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

// The dashboard link is "exact": it must not stay highlighted on /owner/dashboard/... sub-pages.
const isDashboardActive = () => isActive('/owner/dashboard') && !isActive('/owner/dashboard/');

const link = (href, text, icon, extra = {}) => ({ href, text, icon, active: isActive(href), ...extra });

const navGroups = computed(() => {
    page.url; // re-evaluate the active item whenever the page changes
    if (isSuspended.value) {
        return [
            {
                items: [
                    { href: '/owner/dashboard', text: 'Suspension notice', icon: 'alert', active: isDashboardActive(), tone: 'danger' },
                    link('/owner/orders', 'Process orders', 'clipboard'),
                    link('/owner/staff', 'Staff accounts', 'team'),
                    link('/owner/audit-logs', 'Audit logs', 'document'),
                ],
            },
        ];
    }

    return [
        { items: [{ href: '/owner/dashboard', text: 'Dashboard', icon: 'dashboard', active: isDashboardActive() }] },
        {
            label: 'Operations',
            items: [
                link('/owner/pos', 'Start POS', 'pos', { tone: 'cta' }),
                link('/owner/inventory', 'Inventory', 'box'),
            ],
        },
        {
            label: 'Procurement',
            items: [
                link('/owner/suppliers', 'Suppliers', 'building'),
                link('/owner/purchase-orders', 'Purchase orders', 'document'),
            ],
        },
        {
            label: 'Sales',
            items: [
                link('/owner/orders', 'Orders', 'clipboard'),
                link('/owner/messages', 'Messages', 'chat'),
            ],
        },
        {
            label: 'Business management',
            items: isOwner.value
                ? [
                    link('/owner/insights', 'Insights', 'chart'),
                    link('/owner/sales', 'Financial reports', 'currency'),
                    link('/owner/staff', 'Staff management', 'team'),
                    link('/owner/profile/edit', 'Business profile', 'briefcase'),
                ]
                : [],
        },
    ];
});

const quickActions = [
    { href: '/owner/inventory/create', text: 'Add product', icon: 'plus' },
    { href: '/owner/orders?status=pending', text: 'View pending orders', icon: 'clock' },
    { href: '/owner/dashboard#inventory-alerts', text: 'View alerts', icon: 'bell' },
];

const bottomTabs = computed(() => {
    page.url; // re-evaluate the active tab whenever the page changes
    return [
        { href: '/owner/dashboard', text: 'Home', icon: 'dashboard', active: isDashboardActive() },
        { href: '/owner/messages', text: 'Messages', icon: 'chat', active: isActive('/owner/messages'), badge: unreadChatMessages.value },
        { href: '/owner/pos', text: 'POS', icon: 'pos', active: isActive('/owner/pos') },
        { href: '/owner/inventory', text: 'Stock', icon: 'box', active: isActive('/owner/inventory') },
        { href: '/owner/orders', text: 'Orders', icon: 'clipboard', active: isActive('/owner/orders') },
    ];
});
</script>
