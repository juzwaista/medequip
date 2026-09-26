<template>
    <svg
        viewBox="0 0 48 48"
        :width="size"
        :height="size"
        fill="currentColor"
        aria-hidden="true"
        focusable="false"
    >
        <!-- Sampaguita bloom: four small pale petals behind four main petals that read as a plus. -->
        <g fill-opacity=".38">
            <path v-for="angle in [45, 135, 225, 315]" :key="'s' + angle" :transform="`rotate(${angle} 24 24)`" d="M24 24.5C19.6 20.2 19.8 13.6 24 8.6C28.2 13.6 28.4 20.2 24 24.5Z" />
        </g>
        <g :stroke="gap" stroke-width="1.6" paint-order="stroke">
            <path v-for="angle in [0, 90, 180, 270]" :key="'p' + angle" :transform="`rotate(${angle} 24 24)`" d="M24 25.5C17 19.5 17.4 9.5 24 3.5C30.6 9.5 31 19.5 24 25.5Z" />
        </g>
    </svg>
</template>

<script setup>
defineProps({
    size: { type: [Number, String], default: 36 },
    // Color of the thin outline that separates the main petals; match the surface the mark sits on.
    gap: { type: String, default: '#ffffff' },
});
</script>
