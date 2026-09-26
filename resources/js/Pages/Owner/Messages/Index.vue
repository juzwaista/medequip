<template>
    <OwnerLayout>
        <div class="max-w-5xl mx-auto w-full">
            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-ink">Customer messages</h1>
                <p class="text-ink-soft mt-1 text-sm">Direct messages from your customers.</p>
            </div>

            <div v-if="!conversations.length" class="bg-white rounded-card shadow border border-line p-12 text-center text-ink-soft">
                No customer messages yet. When buyers message you from a product or your shop profile, threads appear here.
            </div>

            <ul v-else class="space-y-2">
                <li v-for="c in conversations" :key="c.id">
                    <Link
                        :href="`/owner/messages/${c.id}`"
                        class="block min-h-[4.5rem] bg-white rounded-card shadow-sm border border-line p-4 hover:border-brand-soft hover:shadow transition"
                    >
                        <div class="flex justify-between gap-2 items-start">
                            <div class="min-w-0 flex items-start gap-2">
                                <span
                                    v-if="c.unread_count > 0"
                                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand"
                                    aria-hidden="true"
                                />
                                <div class="min-w-0">
                                <p class="font-semibold text-ink truncate">{{ c.customer.name }}</p>
                                <p v-if="c.context_product" class="text-xs text-ink-soft mt-0.5 truncate">
                                    Re: {{ c.context_product.name }}
                                </p>
                                <p class="text-sm text-ink-soft mt-1 line-clamp-2">{{ c.preview || 'No messages yet' }}</p>
                                </div>
                            </div>
                            <span v-if="c.last_message_at" class="text-xs text-ink-faint shrink-0 tabular-nums">{{ formatWhen(c.last_message_at) }}</span>
                        </div>
                    </Link>
                </li>
            </ul>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

defineProps({
    conversations: { type: Array, default: () => [] },
});

function formatWhen(iso) {
    try {
        return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch {
        return '';
    }
}
</script>
