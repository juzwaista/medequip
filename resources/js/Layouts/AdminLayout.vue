<template>
    <div class="flex h-[100dvh] min-h-0 overflow-hidden bg-mist text-ink">
        <FlashMessage />

        <PortalSidebar
            :groups="navGroups"
            portal-label="Admin panel"
            home-href="/admin/dashboard"
            :open="mobileSidebarOpen"
            @close="mobileSidebarOpen = false"
        />

        <!-- Main workspace -->
        <div class="flex h-[100dvh] min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-white px-4 lg:px-8">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = true" class="rounded-control p-1.5 text-ink-soft hover:text-ink focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand lg:hidden" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>

                <div class="flex items-center">
                    <AccountMenu
                        v-if="$page.props.auth.user"
                        :userName="$page.props.auth.user.name"
                        :userEmail="$page.props.auth.user.email"
                        :userRole="$page.props.auth.user.role"
                        :csrfToken="csrfToken"
                    />
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div v-if="globalError" class="whitespace-pre-wrap bg-danger p-6 font-mono text-sm text-white">
                    VUE ERROR: {{ globalError }}
                </div>
                <div v-else>
                    <div v-if="$slots.header" class="shrink-0 border-b border-line bg-white px-4 py-6 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                    <div class="min-w-0 overflow-x-auto p-3 sm:p-6 lg:p-8">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onErrorCaptured } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AccountMenu from '@/Components/AccountMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PortalSidebar from '@/Components/ui/PortalSidebar.vue';

const globalError = ref(null);
onErrorCaptured((err, instance, info) => {
    globalError.value = String(err) + '\n\nInfo: ' + info;
    return false; // Stop propagation
});

const page = usePage();
const mobileSidebarOpen = ref(false);

const isSuperAdmin = computed(() => {
    return page.props.auth?.user?.role === 'super_admin';
});

/**
 * Check if the current admin user has a specific permission.
 * Super admins always return true (they bypass all restrictions).
 * Regular admins check against the shared admin_permissions array.
 */
const canDo = (permission) => {
    if (isSuperAdmin.value) return true;
    const perms = page.props.admin_permissions ?? [];
    return perms.includes(permission);
};

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

const navGroups = computed(() => {
    page.url; // re-evaluate the active item whenever the page changes
    const item = (href, text, icon, extra = {}) => ({ href, text, icon, active: isActive(href), ...extra });

    const groups = [
        {
            items: [
                // The dashboard link must not stay highlighted on /admin/dashboard/... sub-pages.
                { href: '/admin/dashboard', text: 'Dashboard', icon: 'dashboard', active: isActive('/admin/dashboard') && !isActive('/admin/dashboard/') },
                item('/admin/reports', 'Reports hub', 'alert', { badge: openReportCount.value, badgeTone: 'danger' }),
            ],
        },
        {
            label: 'Management',
            items: [
                item('/admin/users', 'Users and shops', 'users', { badge: pendingVerifications.value }),
                ...(canDo('admin.applications.review') ? [item('/admin/business-profiles', 'B2B accounts', 'briefcase')] : []),
                ...(canDo('admin.orders.view') ? [item('/admin/orders', 'Orders', 'clipboard')] : []),
                ...(canDo('admin.products.view') ? [item('/admin/products', 'Products', 'box')] : []),
                ...(canDo('admin.couriers.create') || isSuperAdmin.value
                    ? [{ href: '/admin/couriers', text: 'Couriers', icon: 'swap', active: isActive('/admin/couriers') && !isActive('/admin/couriers/deliveries') }]
                    : []),
            ],
        },
        {
            label: 'System',
            items: [
                ...(canDo('admin.disputes.review') ? [item('/admin/reviews/disputes', 'Review disputes', 'alert')] : []),
                item('/admin/audit-logs', 'Audit log', 'document'),
            ],
        },
    ];

    if (page.props.auth?.user?.role === 'super_admin') {
        groups.push({
            label: 'Super admin',
            items: [
                item('/admin/roles', 'Platform roles', 'shield'),
                item('/superadmin/staff', 'Platform staff', 'shield'),
                item('/superadmin/couriers/deliveries', 'Global deliveries', 'truck'),
                item('/superadmin/settings', 'Settings', 'cog'),
            ],
        });
    }

    return groups;
});
</script>
