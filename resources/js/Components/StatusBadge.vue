<template>
    <div 
        :class="[statusClasses, 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold capitalize border shadow-sm backdrop-blur-sm']"
    >
        <!-- Dynamic bullet indicator -->
        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-75 mr-0.5"></span>
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

const statusClasses = computed(() => {
    const status = props.status.toLowerCase();
    
    // Order status colors
    if (props.type === 'order') {
        const colors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'approved': 'bg-blue-100 text-blue-800',
            'processing': 'bg-blue-100 text-blue-800',
            'packed': 'bg-purple-100 text-purple-800',
            'ready_for_pickup': 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'shipped': 'bg-indigo-100 text-indigo-800',
            'delivered': 'bg-green-100 text-green-800',
            'rejected': 'bg-red-100 text-red-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    // Invoice status colors
    if (props.type === 'invoice') {
        const colors = {
            'unpaid': 'bg-yellow-100 text-yellow-800',
            'partial': 'bg-orange-100 text-orange-800',
            'paid': 'bg-green-100 text-green-800',
            'overdue': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    // Delivery status colors
    if (props.type === 'delivery') {
        const colors = {
            'pending': 'bg-gray-100 text-gray-800',
            'scheduled': 'bg-blue-100 text-blue-800',
            'in_transit': 'bg-indigo-100 text-indigo-800',
            'delivered': 'bg-green-100 text-green-800',
            'failed': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    // Payment status colors
    if (props.type === 'payment') {
        const colors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'verified': 'bg-green-100 text-green-800',
            'rejected': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    return 'bg-gray-100 text-gray-800';
});

const displayText = computed(() => {
    return props.status.replace(/_/g, ' ');
});
</script>
