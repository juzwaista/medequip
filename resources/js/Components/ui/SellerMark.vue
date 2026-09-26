<template>
    <span class="flex min-w-0 max-w-full items-center gap-1.5 text-sm text-ink-soft">
        <svg
            v-if="verified"
            class="h-4 w-4 flex-none text-seal"
            viewBox="0 0 20 20"
            role="img"
            aria-label="Verified seller"
        >
            <title>Verified seller</title>
            <path fill="currentColor" d="M10 1l2.2 1.2 2.5-.1 1.2 2.2 2.1 1.3-.1 2.5L19 10l-1.1 2.2.1 2.5-2.2 1.2-1.3 2.1-2.5-.1L10 19l-2.2-1.1-2.5.1-1.2-2.2-2.1-1.3.1-2.5L1 10l1.1-2.2-.1-2.5 2.2-1.2 1.3-2.1 2.5.1z" />
            <path d="M6.6 10.2l2.4 2.4 4.5-4.8" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <Link v-if="href" :href="href" class="min-w-0 truncate hover:text-ink hover:underline underline-offset-2">{{ name }}</Link>
        <span v-else class="min-w-0 truncate">{{ name }}</span>
    </span>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    name: { type: String, required: true },
    verified: { type: Boolean, default: false },
    href: { type: String, default: null },
});
</script>
