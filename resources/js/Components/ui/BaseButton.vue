<template>
    <Link v-if="href" :href="href" :class="classes" v-bind="$attrs">
        <slot />
    </Link>
    <button v-else :type="type" :class="classes" v-bind="$attrs">
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    variant: { type: String, default: 'primary', validator: (v) => ['primary', 'secondary', 'ghost'].includes(v) },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
    // When set, renders an Inertia link instead of a <button>.
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
});

const variants = {
    primary: 'bg-brand text-white hover:bg-brand-dark border border-transparent',
    secondary: 'bg-white text-brand border border-brand hover:bg-brand hover:text-white',
    ghost: 'bg-transparent text-ink border border-transparent hover:bg-mist',
};

const sizes = {
    sm: 'h-9 px-3.5 text-sm',
    md: 'h-10 px-4',
};

const classes = computed(() => [
    'inline-flex items-center justify-center whitespace-nowrap rounded-control font-medium transition-colors',
    'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand',
    'disabled:cursor-not-allowed disabled:border-line disabled:bg-mist disabled:text-ink-faint',
    variants[props.variant],
    sizes[props.size],
]);
</script>
