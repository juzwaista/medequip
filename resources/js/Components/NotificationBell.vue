<template>
    <div class="relative" ref="containerRef">
        <button
            type="button"
            class="p-3 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition relative"
            title="Notifications"
            @click="toggleModal"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="count > 0"
                class="absolute top-1 right-1 bg-rose-500 text-white text-[11px] font-black rounded-full min-w-[1.25rem] h-5 px-1 flex items-center justify-center leading-none"
            >
                {{ count > 9 ? '9+' : count }}
            </span>
        </button>

        <!-- Notification trigger button -->

        <!-- Dropdown Panel -->
        <transition
            enter-active-class="transition ease-out duration-200 transform origin-top-right"
            enter-from-class="opacity-0 scale-95 -translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150 transform origin-top-right"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-2"
        >
            <div 
                v-if="isOpen" 
                class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-96 bg-white shadow-2xl ring-1 ring-black/5 rounded-2xl z-[100] flex flex-col max-h-[85vh] sm:max-h-[32rem] overflow-hidden"
            >
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50/80 shrink-0">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold tracking-tight text-gray-900">Notifications</h2>
                        <span v-if="count > 0" class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">{{ count }} new</span>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="closeModal">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto w-full overscroll-contain">
                    <div v-if="loading" class="py-12 flex justify-center">
                        <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div v-else-if="items.length === 0" class="py-12 px-6 text-center">
                        <div class="mx-auto w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <p class="text-sm font-bold text-gray-900">You're all caught up!</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Check back later for updates and alerts.</p>
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div 
                            v-for="item in items" 
                            :key="item.id" 
                            class="p-4 hover:bg-gray-50/80 transition-colors group cursor-pointer"
                            :class="{ 'bg-blue-50/40': !item.read_at }"
                            @click="handleItemClick(item)"
                        >
                            <div class="flex items-start gap-3 w-full">
                                <span
                                    class="shrink-0 mt-0.5 w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-white shadow-sm"
                                    :class="iconBg(item)"
                                    v-html="iconSvg(item)"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between gap-1 items-start">
                                        <p class="text-sm font-bold text-gray-900 leading-snug">
                                            {{ item.data.title || 'Notification' }}
                                        </p>
                                        <span
                                            v-if="!item.read_at"
                                            class="shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-1.5"
                                        ></span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 leading-relaxed line-clamp-2">
                                        {{ item.data.body || item.data.preview || '' }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <p class="text-[10px] sm:text-xs text-gray-400 font-medium">{{ formatTime(item.created_at) }}</p>
                                        <span
                                            v-if="kindLabel(item)"
                                            class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full"
                                            :class="kindBadge(item)"
                                        >{{ kindLabel(item) }}</span>
                                    </div>
                                    <div v-if="item.data.action_href && item.data.action_label" class="mt-2.5">
                                        <span class="text-[11px] font-bold text-blue-600 group-hover:underline">
                                            {{ item.data.action_label }} &rarr;
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="items.length > 0" class="p-3 border-t border-gray-100 bg-gray-50/50 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]">
                    <button
                        type="button"
                        class="w-full py-2 bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-bold transition-colors shadow-sm"
                        @click="markAllRead"
                    >
                        Mark all as read
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    count: {
        type: Number,
        default: 0,
    },
});

const isOpen = ref(false);
const loading = ref(false);
const items = ref([]);
const containerRef = ref(null);

function onClickOutside(e) {
    if (isOpen.value && containerRef.value && !containerRef.value.contains(e.target)) {
        closeModal();
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside);
});

function toggleModal() {
    if (isOpen.value) {
        closeModal();
    } else {
        openModal();
    }
}

async function openModal() {
    isOpen.value = true;
    loading.value = true;
    try {
        const response = await window.axios.get('/notifications', { headers: { Accept: 'application/json' } });
        items.value = response.data.notifications?.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function closeModal() {
    isOpen.value = false;
}

function handleItemClick(item) {
    if (!item.read_at) {
        window.axios.post(`/notifications/${item.id}/read`).catch(() => {});
        item.read_at = new Date().toISOString(); 
    }
    
    // Attempt standard action href link first
    let href = item.data?.action_href;
    
    // Provide fallback routing identical to logic in Notifications/Index.vue
    if (!href) {
        const kind = item.data?.kind || '';
        const id = item.data?.order_id;
        
        if (kind === 'welcome') href = '/products';
        else if (kind === 'new_chat_message') href = '/messages';
        else if (id) {
            // Need to deduce role if we navigate (a generic /orders will redirect, but owner goes to /owner/orders)
            href = window.location.pathname.startsWith('/owner') ? `/owner/orders/${id}` : `/orders/${id}`;
        }
    }

    if (href) {
        closeModal();
        router.visit(href);
    }
}

async function markAllRead() {
    try {
        await window.axios.post('/notifications/read-all');
        items.value.forEach(i => i.read_at = i.read_at || new Date().toISOString());
        setTimeout(() => closeModal(), 400); 
    } catch (e) {
        console.error(e);
    }
}

// Visuals port from Index.vue
const svgIcons = {
    welcome: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
    order_placed: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    order_accepted: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    order_rejected: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    order_packed: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    order_shipped: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>',
    order_delivered: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    order_cancelled: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
    order_completed: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>',
    payment_confirmed: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    prescription: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    review_prompt: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
    chat: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
    moderation: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    account_warning: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
    default: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
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
    account_warning: 'bg-rose-500',
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
    account_warning: 'Account',
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
    account_warning: 'bg-rose-50 text-rose-800',
};

function kindLabel(n) {
    return labelMap[n.data?.kind] || null;
}

function kindBadge(n) {
    return badgeMap[n.data?.kind] || 'bg-gray-100 text-gray-600';
}

function formatTime(iso) {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        const diffMs = new Date() - d;
        const diffMins = Math.floor(diffMs / 60000);
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        const diffHrs = Math.floor(diffMins / 60);
        if (diffHrs < 24) return `${diffHrs}h ago`;
        
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    } catch {
        return '';
    }
}
</script>
