<template>
    <div class="relative" ref="containerRef">
        <button
            type="button"
            class="p-3 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition relative"
            :title="title"
            @click="toggleModal"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
            </svg>
            <span
                v-if="count > 0"
                class="absolute top-1 right-1 bg-blue-600 text-white text-[11px] font-black rounded-full min-w-[1.25rem] h-5 px-1 flex items-center justify-center leading-none"
            >
                {{ count > 9 ? '9+' : count }}
            </span>
        </button>

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
                class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-96 bg-white shadow-2xl ring-1 ring-black/5 rounded-2xl z-[100] flex flex-col max-h-[85vh] sm:max-h-[36rem] overflow-hidden"
            >
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80 shrink-0">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold tracking-tight text-gray-900">Messages</h2>
                            <span v-if="count > 0" class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">{{ count }} new</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link :href="href" class="text-xs text-blue-600 font-bold hover:underline" @click="closeModal">View all</Link>
                            <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="closeModal">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                    <!-- Filters -->
                    <div class="flex gap-2">
                        <button v-for="f in availableFilters" :key="f" @click="activeFilter = f"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-colors"
                            :class="activeFilter === f ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            {{ f }}
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto w-full overscroll-contain">
                    <div v-if="loading" class="py-12 flex justify-center">
                        <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    <div v-else-if="filteredItems.length === 0" class="py-12 px-6 text-center">
                        <div class="mx-auto w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-gray-900">No messages found</p>
                        <p class="text-xs text-gray-500 mt-0.5">Try changing your filters or check back later.</p>
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div 
                            v-for="item in filteredItems" 
                            :key="item.id" 
                            class="p-4 hover:bg-gray-50/80 transition-colors cursor-pointer group"
                            :class="{ 'bg-blue-50/40': item.unread_count > 0 }"
                            @click="openChat(item)"
                        >
                            <div class="flex items-start gap-3 w-full">
                                <div class="w-10 h-10 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold uppercase overflow-hidden">
                                    <span v-if="!item.customer?.profile_photo_url">{{ (item.customer?.name || item.shop?.company_name || '?')[0] }}</span>
                                    <img v-else :src="item.customer.profile_photo_url" class="object-cover w-full h-full" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm font-bold text-gray-900 truncate">
                                            {{ item.customer?.name || item.shop?.company_name || 'User' }}
                                        </p>
                                        <p class="text-[10px] sm:text-xs text-gray-400 font-medium shrink-0 whitespace-nowrap ml-2">
                                            {{ formatTime(item.last_message_at) }}
                                        </p>
                                    </div>
                                    <p v-if="item.context_product" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5 truncate">
                                        Re: {{ item.context_product.name }}
                                    </p>
                                    <div class="flex items-start gap-1 mt-1">
                                        <div v-if="item.unread_count > 0" class="shrink-0 w-2 h-2 rounded-full bg-blue-600 mt-1.5" />
                                        <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed" :class="{'font-medium text-gray-900': item.unread_count > 0}">
                                            {{ item.preview || 'No messages yet' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Floating Chat Modal (Facebook style) -->
        <Teleport to="body">
            <div 
                v-if="activeChat" 
                class="fixed bottom-0 right-4 sm:right-6 z-[120] w-full sm:w-[320px] md:w-[340px] bg-white rounded-t-2xl shadow-[0_-8px_30px_rgba(0,0,0,0.12)] border border-gray-200 border-b-0 flex flex-col transition-transform transform duration-300 origin-bottom"
                :class="isChatMinimized ? 'h-12 translate-y-0' : 'h-[65vh] sm:h-[420px] translate-y-0'"
            >
                <!-- Chat Header -->
                <div 
                    class="h-12 px-4 bg-blue-600 rounded-t-2xl flex items-center justify-between cursor-pointer text-white shrink-0"
                    @click="isChatMinimized = !isChatMinimized"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative w-7 h-7 rounded-full bg-white/20 flex items-center justify-center font-bold uppercase shrink-0 text-sm">
                            {{ (activeChat.customer?.name || activeChat.shop?.company_name || '?')[0] }}
                            <span v-if="activeChat.presence === 'Online'" class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 border-[1.5px] border-blue-600 rounded-full"></span>
                        </div>
                        <div class="min-w-0 flex flex-col justify-center">
                            <p class="font-bold text-[13px] truncate leading-tight">{{ activeChat.customer?.name || activeChat.shop?.company_name || 'User' }}</p>
                            <p v-if="activeChat.presence === 'Online'" class="text-[10px] text-blue-100 font-medium leading-none mt-0.5">Active now</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" class="p-1.5 hover:bg-white/10 rounded-lg transition" @click.stop="closeChat">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Chat Body (Mounted when not minimized) -->
                <div v-show="!isChatMinimized" class="flex-1 min-h-0 bg-white relative flex flex-col">
                    <OrderChatPanel
                        class="flex-1 min-h-0"
                        fill-viewport
                        :fetch-url="activeChat.fetchUrl"
                        :post-url="activeChat.postUrl"
                        :mark-read-url="activeChat.markReadUrl"
                        :viewer-role="activeChat.viewerRole"
                        :show-header="false"
                        :enable-report="true"
                        @update:presence="activeChat.presence = $event"
                    />
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OrderChatPanel from './OrderChatPanel.vue';

const props = defineProps({
    href: { type: String, required: true },
    count: {
        type: Number,
        default: 0,
    },
    title: {
        type: String,
        default: 'Messages — chat with sellers or customers anytime',
    },
});

const isOpen = ref(false);
const loading = ref(false);
const items = ref([]);
const activeFilter = ref('All');
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

const activeChat = ref(null);
const isChatMinimized = ref(false);

const availableFilters = computed(() => {
    return window.location.pathname.startsWith('/owner') 
        ? ['All', 'Unread', 'Rx Action'] 
        : ['All', 'Unread'];
});

const filteredItems = computed(() => {
    let list = items.value;
    if (activeFilter.value === 'Unread') {
        list = list.filter(i => (i.unread_count || 0) > 0);
    } else if (activeFilter.value === 'Rx Action') {
        list = list.filter(i => !!i.has_action_required);
    }
    return list;
});

function toggleModal() {
    if (isOpen.value) closeModal();
    else openModal();
}

async function openModal() {
    isOpen.value = true;
    loading.value = true;
    try {
        const response = await window.axios.get(props.href, { headers: { Accept: 'application/json' } });
        items.value = response.data.conversations || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function closeModal() {
    isOpen.value = false;
}

function openChat(item) {
    activeChat.value = item;
    isChatMinimized.value = false;
    closeModal();
    
    // Optimistically mark read on the local data list
    item.unread_count = 0;
}

function closeChat() {
    activeChat.value = null;
    isChatMinimized.value = false;
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
