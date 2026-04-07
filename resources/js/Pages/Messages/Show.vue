<template>
    <MainLayout>
        <div
            class="flex flex-col flex-1 min-h-0 w-full max-w-none min-h-[calc(100dvh-5rem-5rem)] sm:min-h-[calc(100dvh-5rem)] md:min-h-[calc(100dvh-6rem)]"
        >
            <OrderChatPanel
                class="flex-1 min-h-0"
                fill-viewport
                back-href="/messages"
                back-label="Back to all messages"
                :title-href="conversation.shop?.slug ? `/seller/${conversation.shop.slug}` : null"
                :counterpart-presence="counterpartPresence"
                :header-title="panelTitle"
                :header-subtitle="threadSubtitle"
                :fetch-url="fetchUrl"
                :post-url="postUrl"
                :mark-read-url="markReadUrl"
                :viewer-role="viewerRole"
                :enable-report="true"
            />
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import OrderChatPanel from '@/Components/OrderChatPanel.vue';

const props = defineProps({
    conversation: Object,
    viewerRole: String,
    fetchUrl: String,
    postUrl: String,
    markReadUrl: String,
    counterpartPresence: { type: String, default: '' },
    counterpartLastSeenAt: { type: String, default: null },
});

const panelTitle = computed(() => {
    if (props.viewerRole === 'customer') {
        return props.conversation?.shop?.company_name || 'Messages';
    }
    return 'Messages';
});

const threadSubtitle = computed(() => {
    if (props.viewerRole === 'customer') {
        return 'Direct message with this seller.';
    }
    return 'Direct message with this customer.';
});
</script>
