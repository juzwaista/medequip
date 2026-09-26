<template>
    <!-- Backdrop for the phone drawer -->
    <transition
        enter-active-class="transition-opacity ease-linear duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-linear duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="open" class="fixed inset-0 z-40 bg-ink/70 lg:hidden" @click="$emit('close')"></div>
    </transition>

    <aside
        :class="[
            'fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-ink text-white transition-transform duration-300 ease-in-out lg:static lg:translate-x-0',
            open ? 'translate-x-0' : '-translate-x-full',
        ]"
        aria-label="Main navigation"
    >
        <!-- Logo and portal name -->
        <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4">
            <Link :href="homeHref" class="flex min-w-0 flex-col rounded-control focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                <BrandLogo light :size="30" gap="#12262B" />
            </Link>
            <button type="button" @click="$emit('close')" class="rounded-control p-1.5 text-white/60 hover:text-white lg:hidden" aria-label="Close menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="border-b border-white/10 px-4 py-2 text-sm text-white/50">{{ portalLabel }}</p>

        <nav class="flex-1 overflow-y-auto px-3 py-3">
            <div v-for="(group, gi) in visibleGroups" :key="gi" :class="gi > 0 ? 'mt-4' : ''">
                <p v-if="group.label" class="px-3 pb-1 pt-1 text-xs font-medium text-white/40">{{ group.label }}</p>
                <ul class="space-y-0.5">
                    <li v-for="item in group.items" :key="item.href">
                        <Link
                            :href="item.href"
                            :aria-current="item.active ? 'page' : undefined"
                            :class="linkClass(item)"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <path v-for="d in (portalIcons[item.icon] || [])" :key="d" :d="d" />
                            </svg>
                            <span class="min-w-0 flex-1 truncate">{{ item.text }}</span>
                            <span
                                v-if="item.badge > 0"
                                class="rounded-full px-1.5 py-0.5 text-center text-xs font-semibold leading-none tabular-nums text-white"
                                :class="item.badgeTone === 'danger' ? 'bg-danger' : 'bg-amber-600'"
                            >
                                {{ item.badge > 99 ? '99+' : item.badge }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <div v-if="$slots.footer" class="shrink-0 border-t border-white/10 px-3 py-4">
            <slot name="footer" />
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BrandLogo from '@/Components/ui/BrandLogo.vue';
import { portalIcons } from '@/utils/portalIcons.js';

const props = defineProps({
    // [{ label?: string, items: [{ href, text, icon, active, badge?, badgeTone?, tone?: 'default'|'cta'|'danger' }] }]
    groups: { type: Array, required: true },
    portalLabel: { type: String, default: '' },
    homeHref: { type: String, default: '/' },
    open: { type: Boolean, default: false },
});

defineEmits(['close']);

// Groups with every item filtered out are hidden entirely.
const visibleGroups = computed(() => props.groups.filter((g) => g.items.length > 0));

const linkClass = (item) => {
    const base = 'flex items-center gap-3 rounded-control border-l-2 px-3 py-2 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-white';
    if (item.tone === 'cta') {
        return [base, 'border-transparent', item.active ? 'bg-brand-dark text-white' : 'bg-brand text-white hover:bg-brand-dark'];
    }
    if (item.active) {
        return [base, item.tone === 'danger' ? 'border-red-400 bg-red-500/20 text-red-100' : 'border-brand-soft bg-white/10 text-white'];
    }
    return [base, 'border-transparent text-white/65 hover:bg-white/5 hover:text-white'];
};
</script>
