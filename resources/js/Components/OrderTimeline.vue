<template>
    <div class="w-full">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Order Progress</h3>
        
        <!-- Timeline -->
        <div class="relative">
            <!-- Progress Line -->
            <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200"  aria-hidden="true"></div>
            <div 
                class="absolute top-5 left-0 h-1 bg-blue-600 transition-all duration-500" 
                :style="{ width: progressWidth }"
                aria-hidden="true"
            ></div>

            <!-- Timeline Stages -->
            <div class="relative flex justify-between">
                <div 
                    v-for="(stage, index) in stages" 
                    :key="stage.status"
                    class="flex flex-col items-center"
                    :class="{ 'flex-1': index !== stages.length - 1 }"
                >
                    <!-- Stage Node -->
                    <div 
                        class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full border-4 transition-all"
                        :class="{
                            'bg-blue-600 border-blue-600': stage.completed,
                            'bg-white border-blue-600': stage.current,
                            'bg-white border-gray-300': !stage.completed && !stage.current
                        }"
                    >
                        <!-- Checkmark for completed -->
                        <svg 
                            v-if="stage.completed"
                            class="w-5 h-5 text-white" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        
                        <!-- Pulse animation for current -->
                        <span 
                            v-else-if="stage.current"
                            class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75 animate-ping"
                        ></span>
                        <span 
                            v-if="stage.current"
                            class="relative inline-flex rounded-full h-3 w-3 bg-blue-600"
                        ></span>
                    </div>

                    <!-- Stage Label -->
                    <div class="mt-3 text-center">
                        <p 
                            class="text-xs font-semibold"
                            :class="{
                                'text-blue-600': stage.completed || stage.current,
                                'text-gray-400': !stage.completed && !stage.current
                            }"
                        >
                            {{ stage.label }}
                        </p>
                        <p 
                            v-if="stage.date"
                            class="text-xs text-gray-500 mt-1"
                        >
                            {{ formatDate(stage.date) }}
                        </p>
                    </div>
                </div>
            </div>
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
    packed: 'Packed & ready to ship',
    shipped: 'Picked up · out for delivery',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
    rejected: 'Rejected',
};

const stages = computed(() => {
    // If cancelled or rejected, show special timeline
    if (props.currentStatus === 'cancelled' || props.currentStatus === 'rejected') {
        return [
            {
                status: 'created',
                label: 'Order Created',
                completed: true,
                current: false,
                date: props.createdAt
            },
            {
                status: props.currentStatus,
                label: statusLabels[props.currentStatus],
                completed: true,
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
