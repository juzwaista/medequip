<template>
    <div 
        class="relative inline-flex items-center justify-center"
        @mouseenter="show = true"
        @mouseleave="show = false"
        @focusin="show = true"
        @focusout="show = false"
    >
        <slot />

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div 
                v-if="show" 
                class="absolute z-[100] px-3 py-2 text-xs font-medium text-white bg-ink/95  rounded-control shadow-xl pointer-events-none whitespace-normal break-words w-max max-w-[250px]"
                :class="positionClasses"
            >
                <div 
                    class="absolute w-2 h-2 bg-ink/95 transform rotate-45"
                    :class="arrowClasses"
                ></div>
                
                <slot name="content">{{ content }}</slot>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    content: {
        type: String,
        default: ''
    },
    position: {
        type: String,
        default: 'top', // top, bottom, left, right
        validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
    }
});

const show = ref(false);

const positionClasses = computed(() => {
    switch (props.position) {
        case 'top': return 'bottom-full left-1/2 -translate-x-1/2 mb-2';
        case 'bottom': return 'top-full left-1/2 -translate-x-1/2 mt-2';
        case 'left': return 'right-full top-1/2 -translate-y-1/2 mr-2';
        case 'right': return 'left-full top-1/2 -translate-y-1/2 ml-2';
    }
});

const arrowClasses = computed(() => {
    switch (props.position) {
        case 'top': return 'bottom-[-4px] left-1/2 -translate-x-1/2';
        case 'bottom': return 'top-[-4px] left-1/2 -translate-x-1/2';
        case 'left': return 'right-[-4px] top-1/2 -translate-y-1/2';
        case 'right': return 'left-[-4px] top-1/2 -translate-y-1/2';
    }
});
</script>
