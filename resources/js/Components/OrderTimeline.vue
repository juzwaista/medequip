<template>
    <div class="w-full">
        <h3 class="mb-4 text-sm font-semibold text-ink">Order progress</h3>

        <!-- Phones: a vertical list, easier to read than five cramped columns -->
        <ol class="md:hidden">
            <li v-for="(stage, index) in stages" :key="stage.status" class="relative flex gap-3 pb-5 last:pb-0">
                <span
                    v-if="index !== stages.length - 1"
                    class="absolute left-[11px] top-6 -bottom-0 w-0.5"
                    :class="stage.completed ? 'bg-brand' : 'bg-line'"
                    aria-hidden="true"
                ></span>
                <span
                    class="relative z-10 flex h-6 w-6 flex-none items-center justify-center rounded-full border-2"
                    :class="nodeClass(stage)"
                >
                    <svg v-if="stage.completed" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    <span v-else-if="stage.current" class="h-2 w-2 rounded-full" :class="stage.status === 'cancelled' || stage.status === 'rejected' ? 'bg-danger' : 'bg-brand'"></span>
                </span>
                <div class="min-w-0 pt-0.5">
                    <p class="text-sm font-medium leading-tight" :class="stage.completed || stage.current ? 'text-ink' : 'text-ink-faint'">
                        {{ stage.label }}
                        <span v-if="stage.current" class="sr-only">(current)</span>
                    </p>
                    <p v-if="stage.date" class="mt-0.5 text-sm text-ink-soft">{{ formatDate(stage.date) }}</p>
                </div>
            </li>
        </ol>

        <!-- Larger screens: horizontal -->
        <div class="relative hidden md:block">
            <div class="absolute left-0 right-0 top-3 h-0.5 bg-line" aria-hidden="true"></div>
            <div
                class="absolute left-0 top-3 h-0.5 bg-brand transition-all duration-500"
                :style="{ width: progressWidth }"
                aria-hidden="true"
            ></div>

            <ol class="relative flex justify-between">
                <li
                    v-for="(stage, index) in stages"
                    :key="stage.status"
                    class="flex flex-col items-center"
                    :class="{ 'flex-1': index !== stages.length - 1 }"
                >
                    <span
                        class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full border-2"
                        :class="nodeClass(stage)"
                    >
                        <svg v-if="stage.completed" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else-if="stage.current" class="h-2 w-2 rounded-full" :class="stage.status === 'cancelled' || stage.status === 'rejected' ? 'bg-danger' : 'bg-brand'"></span>
                    </span>

                    <div class="mt-3 text-center">
                        <p class="text-sm font-medium" :class="stage.completed || stage.current ? 'text-ink' : 'text-ink-faint'">
                            {{ stage.label }}
                            <span v-if="stage.current" class="sr-only">(current)</span>
                        </p>
                        <p v-if="stage.date" class="mt-0.5 text-sm text-ink-soft">{{ formatDate(stage.date) }}</p>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStatus: {
        type: String,
        required: true
    },
    createdAt: {
        type: String,
        required: true
    },
    approvedAt: String,
    packedAt: String,
    shippedAt: String,
    deliveredAt: String,
    cancelledAt: String,
    rejectedAt: String,
});

const statusOrder = ['pending', 'approved', 'packed', 'shipped', 'delivered'];
const statusLabels = {
    pending: 'Waiting for seller',
    approved: 'Seller accepted',
    packed: 'Packed and ready to ship',
    shipped: 'Picked up, out for delivery',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
    rejected: 'Rejected',
};

const nodeClass = (stage) => {
    if (stage.completed) return 'border-brand bg-brand';
    if (stage.current) {
        return stage.status === 'cancelled' || stage.status === 'rejected'
            ? 'border-danger bg-white'
            : 'border-brand bg-white';
    }
    return 'border-line bg-white';
};

const stages = computed(() => {
    // If cancelled or rejected, show special timeline
    if (props.currentStatus === 'cancelled' || props.currentStatus === 'rejected') {
        return [
            {
                status: 'created',
                label: 'Order created',
                completed: true,
                current: false,
                date: props.createdAt
            },
            {
                status: props.currentStatus,
                label: statusLabels[props.currentStatus],
                completed: false,
                current: true,
                date: props.cancelledAt || props.rejectedAt
            }
        ];
    }

    // Normal flow
    const currentIndex = statusOrder.indexOf(props.currentStatus);

    return statusOrder.map((status, index) => {
        let date = null;

        // Map dates to statuses
        if (status === 'pending') date = props.createdAt;
        if (status === 'approved') date = props.approvedAt;
        if (status === 'packed') date = props.packedAt;
        if (status === 'shipped') date = props.shippedAt;
        if (status === 'delivered') date = props.deliveredAt;

        return {
            status,
            label: statusLabels[status],
            completed: index < currentIndex,
            current: index === currentIndex,
            date
        };
    });
});

const progressWidth = computed(() => {
    if (props.currentStatus === 'cancelled' || props.currentStatus === 'rejected') {
        return '50%';
    }

    const currentIndex = statusOrder.indexOf(props.currentStatus);
    if (currentIndex === -1) return '0%';

    const percentage = (currentIndex / (statusOrder.length - 1)) * 100;
    return `${percentage}%`;
});

const formatDate = (dateString) => {
    if (!dateString) return '';

    const date = new Date(dateString);
    const month = date.toLocaleDateString('en-US', { month: 'short' });
    const day = date.getDate();

    return `${month} ${day}`;
};
</script>
