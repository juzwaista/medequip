<template>
    <MainLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-ink">Messages</h1>
                <p class="text-ink-soft mt-1 text-sm">Your conversations with sellers.</p>
            </div>

            <div v-if="!conversations.length" class="bg-white rounded-card shadow border border-line p-12 text-center text-ink-soft">
                <p class="font-medium text-ink">No conversations yet</p>
                <p class="text-sm mt-2">Open a product or seller profile and use <strong>Message seller</strong> to start.</p>
                <Link href="/products" class="inline-block mt-6 text-sm font-semibold text-brand hover:text-brand-dark">Browse products</Link>
            </div>

            <ul v-else class="space-y-2">
                <li v-for="c in conversations" :key="c.id">
                    <Link
                        :href="`/messages/${c.id}`"
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
                                <p class="font-semibold text-ink truncate">{{ c.shop.company_name }}</p>
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
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

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
