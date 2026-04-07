<template>
    <OwnerLayout>
        <div class="flex flex-col flex-1 min-h-0 w-full max-w-none min-h-[calc(100dvh-8rem)] lg:min-h-[calc(100dvh-4rem)]">
            <OrderChatPanel
                class="flex-1 min-h-0"
                fill-viewport
                back-href="/owner/messages"
                back-label="Back to all customer messages"
                :counterpart-presence="counterpartPresence"
                :header-title="conversation.customer?.name || 'Customer'"
                header-subtitle="Direct message with this customer."
                :fetch-url="fetchUrl"
                :post-url="postUrl"
                :mark-read-url="markReadUrl"
                :viewer-role="viewerRole"
                :enable-report="true"
            />
        </div>
    </OwnerLayout>
</template>

<script setup>
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import OrderChatPanel from '@/Components/OrderChatPanel.vue';

defineProps({
    conversation: Object,
    viewerRole: String,
    fetchUrl: String,
    postUrl: String,
    markReadUrl: String,
    counterpartPresence: { type: String, default: '' },
    counterpartLastSeenAt: { type: String, default: null },
});
</script>
