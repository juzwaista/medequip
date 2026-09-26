<template>
    <span :class="amountClass">
        ₱{{ formattedAmount }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    amount: {
        type: [Number, String],
        required: true
    },
    size: {
        type: String,
        default: 'normal' // 'small', 'normal', 'large'
    },
    color: {
        type: String,
        default: 'gray' // 'gray', 'blue', 'green'
    }
});

const formattedAmount = computed(() => {
    return Number(props.amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
});

const amountClass = computed(() => {
    const classes = ['tabular-nums'];
    
    // Size classes
    if (props.size === 'small') {
        classes.push('text-sm');
    } else if (props.size === 'large') {
        classes.push('text-2xl', 'font-semibold', 'tracking-tight');
    } else {
        classes.push('text-base', 'font-semibold');
    }
    
    // Color classes
    if (props.color === 'blue') {
        classes.push('text-brand');
    } else if (props.color === 'green') {
        classes.push('text-brand');
    } else {
        classes.push('text-ink');
    }
    
    return classes.join(' ');
});
</script>
