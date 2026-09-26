<template>
    <!-- class/style go on the wrapper (for layout); every other attribute goes on the <select>. -->
    <div :class="$attrs.class" :style="$attrs.style">
        <label v-if="label" :for="selectId" class="mb-1 block text-sm font-medium text-ink">
            {{ label }}<span v-if="optional" class="ml-1 font-normal text-ink-faint">(optional)</span>
        </label>
        <select
            :id="selectId"
            v-model="model"
            v-bind="selectAttrs"
            :class="[
                'block h-10 w-full rounded-control border bg-white px-3 text-ink',
                'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-mist disabled:text-ink-faint',
                error
                    ? 'border-danger focus:border-danger focus:ring-red-100'
                    : 'border-line focus:border-brand focus:ring-brand-tint',
            ]"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${selectId}-error` : undefined"
        >
            <slot />
        </select>
        <p v-if="error" :id="`${selectId}-error`" class="mt-1 text-sm text-danger">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, useAttrs, useId } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    optional: { type: Boolean, default: false },
});

const model = defineModel();

const attrs = useAttrs();
const generatedId = useId();
const selectId = computed(() => attrs.id || generatedId);

const selectAttrs = computed(() => {
    const { class: _class, style: _style, id: _id, ...rest } = attrs;
    return rest;
});
</script>
