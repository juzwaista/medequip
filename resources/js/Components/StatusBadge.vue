<template>
    <div
        :class="[statusClasses, 'inline-flex items-center gap-1.5 rounded-control border px-2 py-0.5 text-sm font-medium capitalize']"
    >
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>
        {{ displayText }}
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'order' // 'order', 'invoice', 'delivery', 'payment'
    }
});

// One small set of tones shared by every status type.
const tones = {
    waiting: 'border-amber-200 bg-amber-50 text-amber-900',
    active: 'border-[#B9C7DA] bg-[#F1F5FA] text-seal',
    good: 'border-brand-soft bg-brand-tint text-brand-dark',
    done: 'border-brand bg-brand text-white',
    bad: 'border-red-200 bg-red-50 text-red-900',
    neutral: 'border-line bg-mist text-ink-soft',
};

const statusClasses = computed(() => {
    const status = props.status.toLowerCase();

    // Order status colors
    if (props.type === 'order') {
        const colors = {
            pending: tones.waiting,
            approved: tones.active,
            processing: tones.active,
            packed: tones.good,
            ready_for_pickup: tones.good,
            shipped: tones.active,
            delivered: tones.done,
            completed: tones.done,
            rejected: tones.bad,
            cancelled: tones.bad,
        };
        return colors[status] || tones.neutral;
    }

    // Invoice status colors
    if (props.type === 'invoice') {
        const colors = {
            unpaid: tones.waiting,
            partial: tones.waiting,
            paid: tones.done,
            overdue: tones.bad,
        };
        return colors[status] || tones.neutral;
    }

    // Delivery status colors
    if (props.type === 'delivery') {
        const colors = {
            pending: tones.neutral,
            scheduled: tones.active,
            in_transit: tones.active,
            delivered: tones.done,
            failed: tones.bad,
        };
        return colors[status] || tones.neutral;
    }

    // Payment status colors
    if (props.type === 'payment') {
        const colors = {
            pending: tones.waiting,
            verified: tones.done,
            rejected: tones.bad,
        };
        return colors[status] || tones.neutral;
    }

    return tones.neutral;
});

const displayText = computed(() => {
    return props.status.replace(/_/g, ' ');
});
</script>
