<template>
    <span class="inline-flex items-center gap-2.5" :class="light ? 'text-white' : 'text-ink'">
        <BrandMark :size="size" :gap="light ? gap : '#ffffff'" :class="light ? 'text-white' : 'text-brand'" />
        <span class="font-semibold tracking-tight leading-none" :class="{ 'max-[479px]:sr-only': markOnlyOnPhones }" :style="{ fontSize: size * 0.56 + 'px' }">MedEquip</span>
    </span>
</template>

<script setup>
import BrandMark from '@/Components/ui/BrandMark.vue';

defineProps({
    size: { type: Number, default: 36 },
    // Reversed treatment for dark surfaces.
    light: { type: Boolean, default: false },
    // On narrow phones, show only the mark (the name stays available to screen readers).
    markOnlyOnPhones: { type: Boolean, default: false },
    // Outline color for the reversed mark; match the dark surface behind it.
    gap: { type: String, default: '#12262B' },
});
</script>
