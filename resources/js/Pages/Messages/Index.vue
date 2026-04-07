<template>
    <MainLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Messages</h1>
                <p class="text-gray-600 mt-1 text-sm">Your conversations with sellers.</p>
            </div>

            <div v-if="!conversations.length" class="bg-white rounded-xl shadow border border-gray-100 p-12 text-center text-gray-500">
                <p class="font-medium text-gray-700">No conversations yet</p>
                <p class="text-sm mt-2">Open a product or seller profile and use <strong>Message seller</strong> to start.</p>
                <Link href="/products" class="inline-block mt-6 text-sm font-semibold text-blue-600 hover:text-blue-800">Browse products</Link>
            </div>

            <ul v-else class="space-y-2">
                <li v-for="c in conversations" :key="c.id">
                    <Link
                        :href="`/messages/${c.id}`"
                        class="block min-h-[4.5rem] bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:border-blue-200 hover:shadow transition"
                    >
                        <div class="flex justify-between gap-2 items-start">
                            <div class="min-w-0 flex items-start gap-2">
                                <span
                                    v-if="c.unread_count > 0"
                                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-600"
                                    aria-hidden="true"
                                />
                                <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ c.shop.company_name }}</p>
                                <p v-if="c.context_product" class="text-xs text-gray-500 mt-0.5 truncate">
                                    Re: {{ c.context_product.name }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ c.preview || 'No messages yet' }}</p>
                                </div>
                            </div>
                            <span v-if="c.last_message_at" class="text-xs text-gray-400 shrink-0 tabular-nums">{{ formatWhen(c.last_message_at) }}</span>
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
