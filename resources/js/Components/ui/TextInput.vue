<template>
    <!-- class/style go on the wrapper (for layout); every other attribute goes on the <input>. -->
    <div :class="$attrs.class" :style="$attrs.style">
        <label v-if="label" :for="inputId" class="mb-1 block text-sm font-medium text-ink">
            {{ label }}<span v-if="optional" class="ml-1 font-normal text-ink-faint">(optional)</span>
        </label>
        <div class="relative">
            <input
                :id="inputId"
                v-model="model"
                v-bind="inputAttrs"
                :class="[
                    'block h-10 w-full rounded-control border bg-white px-3 text-ink placeholder:text-ink-faint',
                    'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-mist disabled:text-ink-faint',
                    error
                        ? 'border-danger focus:border-danger focus:ring-red-100'
                        : 'border-line focus:border-brand focus:ring-brand-tint',
                    inputClass,
                ]"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
            />
            <!-- Content pinned to the right edge of the field, e.g. a show-password button. -->
            <div v-if="$slots.end" class="absolute inset-y-0 right-0 flex items-center pr-2">
                <slot name="end" />
            </div>
        </div>
        <p v-if="hint && !error" :id="hintId" class="mt-1 text-sm text-ink-soft">{{ hint }}</p>
        <p v-if="error" :id="hintId" class="mt-1 text-sm text-danger">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, useAttrs, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    // Validation message, e.g. `form.errors.field`.
    error: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    optional: { type: Boolean, default: false },
    // Extra classes for the <input> itself, e.g. `pr-10` when using the `end` slot.
    inputClass: { type: String, default: '' },
});

const model = defineModel();

const attrs = useAttrs();
const generatedId = useId();
const inputId = computed(() => attrs.id || generatedId);
const hintId = computed(() => `${inputId.value}-hint`);
const describedBy = computed(() => (props.error || props.hint ? hintId.value : undefined));

const inputAttrs = computed(() => {
    const { class: _class, style: _style, id: _id, ...rest } = attrs;
    return rest;
});
</script>
