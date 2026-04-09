<template>
    <component :is="layoutComponent">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Notifications</h1>
                    <p class="text-gray-600 mt-1 text-sm">Stay updated on your orders and account activity.</p>
                </div>
                <button
                    v-if="notifications.data?.length && hasUnread"
                    type="button"
                    class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="markAllRead"
                >
                    Mark all read
                </button>
            </div>

            <div v-if="!notifications.data?.length" class="bg-white rounded-xl shadow border border-gray-100 p-12 text-center text-gray-500">
                You're all caught up.
            </div>

            <ul v-else class="space-y-4">
                <li
                    v-for="g in displayGroups"
                    :key="g.id"
                >
                    <!-- Single Notification -->
                    <div v-if="!g.isGroup" 
                        class="bg-white rounded-xl shadow-sm border overflow-hidden transition"
                        :class="g.latest.read_at ? 'border-gray-100 opacity-80' : 'border-blue-200 ring-1 ring-blue-100'"
                    >
                        <button
                            type="button"
                            class="block p-4 hover:bg-gray-50/80 text-left w-full"
                            @click="openNotification(g.latest)"
                        >
                            <div class="flex gap-3 items-start">
                                <span
                                    class="shrink-0 mt-0.5 w-9 h-9 rounded-full flex items-center justify-center text-white"
                                    :class="iconBg(g.latest)"
                                    v-html="iconSvg(g.latest)"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between gap-2 items-start">
                                        <p class="font-semibold text-gray-900 text-sm sm:text-base">
                                            {{ g.latest.data?.title || 'Notification' }}
                                        </p>
                                        <span
                                            v-if="!g.latest.read_at"
                                            class="shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ g.latest.data?.body || g.latest.data?.preview || g.latest.data?.message || '' }}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <p class="text-xs text-gray-400">{{ formatWhen(g.latest.created_at) }}</p>
                                        <span
                                            v-if="kindLabel(g.latest)"
                                            class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded-full"
                                            :class="kindBadge(g.latest)"
                                        >{{ kindLabel(g.latest) }}</span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Grouped Notification -->
                    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Group Header -->
                        <div 
                            class="flex items-center justify-between p-4 bg-gray-50/50 cursor-pointer hover:bg-gray-100/50 transition border-b border-gray-100"
                            @click="toggleGroup(g.id)"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center shadow-sm">
                                    <svg v-if="g.type === 'order'" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    <svg v-else class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-black text-gray-900 uppercase tracking-widest">
                                        {{ g.type === 'order' ? 'Order' : 'Chat' }} Updates
                                    </p>
                                    <p class="text-[10px] text-gray-500 font-bold">
                                        {{ g.label }} &middot; {{ g.items.length }} notifications
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="g.unreadCount > 0" class="bg-blue-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full">
                                    {{ g.unreadCount }} new
                                </span>
                                <svg 
                                    class="w-5 h-5 text-gray-400 transition-transform duration-300" 
                                    :class="{'rotate-180': expandedGroups.includes(g.id)}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Latest Item (Always Visible if collapsed, but styled as summary) -->
                        <div v-if="!expandedGroups.includes(g.id)" class="divide-y divide-gray-50">
                            <button
                                type="button"
                                class="block p-4 hover:bg-gray-50/80 text-left w-full"
                                @click="openNotification(g.latest)"
                            >
                                <div class="flex gap-3 items-start">
                                    <span
                                        class="shrink-0 mt-0.5 w-9 h-9 rounded-full flex items-center justify-center text-white"
                                        :class="iconBg(g.latest)"
                                        v-html="iconSvg(g.latest)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex justify-between gap-2 items-start">
                                            <p class="font-semibold text-gray-900 text-sm">
                                                {{ g.latest.data?.title }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 shrink-0">{{ formatWhen(g.latest.created_at) }}</p>
                                        </div>
                                        <p class="text-xs text-gray-600 mt-0.5 line-clamp-1 italic">Latest: {{ g.latest.data?.body || g.latest.data?.preview }}</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- All Items (Expanded) -->
                        <div v-else class="divide-y divide-gray-100 animate-in fade-in slide-in-from-top-2 duration-300">
                            <div 
                                v-for="n in g.items" 
                                :key="n.id"
                                class="p-4 hover:bg-gray-50/80 transition group relative"
                                :class="{'bg-blue-50/30': !n.read_at}"
                            >
                                <div 
                                    class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 scale-y-0 group-hover:scale-y-100 transition-transform"
                                    v-if="!n.read_at"
                                ></div>
                                <button
                                    type="button"
                                    class="text-left w-full"
                                    @click="openNotification(n)"
                                >
                                    <div class="flex gap-3 items-start">
                                        <span
                                            class="shrink-0 mt-0.5 w-8 h-8 rounded-full flex items-center justify-center text-white scale-90"
                                            :class="iconBg(n)"
                                            v-html="iconSvg(n)"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex justify-between gap-2 items-start">
                                                <p class="font-medium text-gray-900 text-sm">
                                                    {{ n.data?.title }}
                                                </p>
                                                <span v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></span>
                                            </div>
                                            <p class="text-xs text-gray-600 mt-0.5">{{ n.data?.body || n.data?.preview }}</p>
                                            <p class="text-[10px] text-gray-400 mt-1">{{ formatWhen(n.created_at) }}</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-if="notifications.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-1 rounded-lg text-sm border"
                    :class="[
                        link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    preserve-state
                    v-html="link.label"
                />
            </div>
        </div>
    </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import CourierLayout from '@/Layouts/CourierLayout.vue';

const props = defineProps({
    notifications: Object,
});

const page = usePage();
const expandedGroups = ref([]);

const toggleGroup = (id) => {
    if (expandedGroups.value.includes(id)) {
        expandedGroups.value = expandedGroups.value.filter(gid => gid !== id);
    } else {
        expandedGroups.value.push(id);
    }
};

const displayGroups = computed(() => {
    const raw = props.notifications.data || [];
    const groups = new Map();
    const finalItems = [];

    raw.forEach(n => {
        let gid = null;
        let groupType = null;
        let groupLabel = '';

        if (n.data?.order_id) {
            gid = `order_${n.data.order_id}`;
            groupType = 'order';
            groupLabel = n.data.order_number || `#${n.data.order_id}`;
        } else if (n.data?.conversation_id) {
            gid = `chat_${n.data.conversation_id}`;
            groupType = 'chat';
            groupLabel = n.data.sender_name || 'Conversation';
        }

        if (gid) {
            if (!groups.has(gid)) {
                const groupObj = {
                    id: gid,
                    isGroup: true,
                    type: groupType,
                    label: groupLabel,
                    items: [],
                    latest: n,
                    unreadCount: 0
                };
                groups.set(gid, groupObj);
                finalItems.push(groupObj);
            }
            const g = groups.get(gid);
            g.items.push(n);
            if (!n.read_at) g.unreadCount++;
            // Since they come pre-sorted by created_at desc, the first one seen is the latest
        } else {
            finalItems.push({
                id: n.id,
                isGroup: false,
                latest: n,
                unreadCount: n.read_at ? 0 : 1
            });
        }
    });

    return finalItems;
});

const layoutComponent = computed(() => {
    const role = page.props.auth?.user?.role;
    if (role === 'courier') return CourierLayout;
    if (role === 'distributor' || role === 'staff') return OwnerLayout;
    return MainLayout;
});

const hasUnread = computed(() => props.notifications.data?.some((n) => !n.read_at));

const svgIcons = {
    welcome: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
    order_placed: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    order_accepted: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    order_rejected: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    order_packed: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    order_shipped: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>',
    order_delivered: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    order_cancelled: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
    order_completed: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>',
    payment_confirmed: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    prescription: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    review_prompt: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
    chat: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
    moderation: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    system_announcement: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1c.256 0 .512.098.707.293l7.414 7.414a1 1 0 01.293.707V17a2 2 0 01-2 2h-1.343M11 5.882l1.343-1.343a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>',
    default: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
};

function iconKey(n) {
    const kind = n.data?.kind || '';
    if (kind.startsWith('prescription') || kind === 'order_requires_prescription') return 'prescription';
    if (kind === 'new_chat_message') return 'chat';
    if (kind.startsWith('distributor_')) return 'moderation';
    if (svgIcons[kind]) return kind;
    return 'default';
}

function iconSvg(n) {
    return svgIcons[iconKey(n)] || svgIcons.default;
}

const bgMap = {
    welcome: 'bg-indigo-500',
    order_placed: 'bg-blue-500',
    order_accepted: 'bg-emerald-500',
    order_rejected: 'bg-red-500',
    order_packed: 'bg-amber-500',
    order_shipped: 'bg-sky-500',
    order_delivered: 'bg-green-600',
    order_cancelled: 'bg-gray-500',
    order_completed: 'bg-emerald-600',
    payment_confirmed: 'bg-green-500',
    prescription: 'bg-violet-500',
    review_prompt: 'bg-yellow-500',
    chat: 'bg-blue-500',
    moderation: 'bg-rose-600',
    system_announcement: 'bg-indigo-600',
    default: 'bg-gray-400',
};

function iconBg(n) {
    const kind = n.data?.kind || '';
    if (kind === 'distributor_suspension_lifted') return 'bg-emerald-600';
    if (kind === 'distributor_warned') return 'bg-amber-500';
    if (kind.startsWith('distributor_')) return 'bg-rose-600';
    return bgMap[iconKey(n)] || bgMap.default;
}

const labelMap = {
    order_placed: 'New order',
    order_accepted: 'Accepted',
    order_rejected: 'Rejected',
    order_packed: 'Packed',
    order_shipped: 'Shipped',
    order_delivered: 'Delivered',
    order_cancelled: 'Cancelled',
    order_completed: 'Completed',
    payment_confirmed: 'Payment',
    order_requires_prescription: 'Prescription',
    prescription_uploaded: 'Prescription',
    prescription_approved: 'Approved',
    prescription_rejected: 'Rejected',
    review_prompt: 'Review',
    distributor_warned: 'Shop',
    distributor_suspended: 'Shop',
    distributor_suspension_lifted: 'Shop',
    distributor_banned: 'Shop',
    system_announcement: 'Announcement',
};

const badgeMap = {
    order_placed: 'bg-blue-50 text-blue-700',
    order_accepted: 'bg-emerald-50 text-emerald-700',
    order_rejected: 'bg-red-50 text-red-700',
    order_packed: 'bg-amber-50 text-amber-700',
    order_shipped: 'bg-sky-50 text-sky-700',
    order_delivered: 'bg-green-50 text-green-700',
    order_cancelled: 'bg-gray-100 text-gray-600',
    order_completed: 'bg-emerald-50 text-emerald-700',
    payment_confirmed: 'bg-green-50 text-green-700',
    order_requires_prescription: 'bg-violet-50 text-violet-700',
    prescription_uploaded: 'bg-violet-50 text-violet-700',
    prescription_approved: 'bg-emerald-50 text-emerald-700',
    prescription_rejected: 'bg-red-50 text-red-700',
    review_prompt: 'bg-yellow-50 text-yellow-700',
    distributor_warned: 'bg-amber-50 text-amber-800',
    distributor_suspended: 'bg-rose-50 text-rose-800',
    distributor_suspension_lifted: 'bg-emerald-50 text-emerald-800',
    distributor_banned: 'bg-gray-800 text-gray-100',
    system_announcement: 'bg-indigo-50 text-indigo-800',
};

function kindLabel(n) {
    return labelMap[n.data?.kind] || null;
}

function kindBadge(n) {
    return badgeMap[n.data?.kind] || 'bg-gray-100 text-gray-600';
}

function resolveHref(n) {
    const href = n.data?.action_href;
    if (typeof href === 'string' && href.length && href.startsWith('/')) {
        return href;
    }

    const kind = n.data?.kind || '';
    if (kind === 'welcome') return '/products';
    if (kind === 'new_chat_message') return '/messages';

    const id = n.data?.order_id;
    const role = page.props.auth?.user?.role;
    if (!id) return '/notifications';
    if (role === 'distributor' || role === 'staff') return `/owner/orders/${id}`;
    if (role === 'courier') return '/courier/deliveries';
    return `/orders/${id}`;
}

function formatWhen(iso) {
    try {
        const d = new Date(iso);
        const now = new Date();
        const diffMs = now - d;
        const diffMin = Math.floor(diffMs / 60000);
        if (diffMin < 1) return 'Just now';
        if (diffMin < 60) return `${diffMin}m ago`;
        const diffHr = Math.floor(diffMin / 60);
        if (diffHr < 24) return `${diffHr}h ago`;
        const diffDay = Math.floor(diffHr / 24);
        if (diffDay < 7) return `${diffDay}d ago`;
        return d.toLocaleDateString();
    } catch {
        return '';
    }
}

function markAllRead() {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}

function openNotification(n) {
    const href = resolveHref(n);
    const markUrl = `/notifications/${n.id}/read`;
    window.axios
        .post(markUrl)
        .catch(() => {})
        .finally(() => {
            router.visit(href);
        });
}
</script>
