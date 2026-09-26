<template>
    <label
        class="flex cursor-pointer items-start gap-3 rounded-card border p-4 transition-colors has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-brand"
        :class="checked ? 'border-brand bg-brand-tint/60' : 'border-line bg-white hover:border-ink-faint'"
    >
        <input
            v-model="model"
            type="radio"
            :value="value"
            :name="name"
            class="mt-0.5 h-4 w-4 flex-none accent-brand"
        />
        <span class="min-w-0">
            <span class="block font-semibold text-ink">{{ title }}</span>
            <span v-if="description" class="mt-0.5 block text-sm text-ink-soft">{{ description }}</span>
        </span>
    </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    // The value this card represents (may be a string or a boolean).
    value: { type: [String, Number, Boolean], required: true },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    name: { type: String, default: undefined },
});

const model = defineModel();
const checked = computed(() => model.value === props.value);
</script>
