<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div v-if="show" class="fixed top-20 right-4 z-50 max-w-sm w-full">
            <div 
                :class="[
                    'rounded-lg shadow-lg p-4 flex items-start space-x-3',
                    type === 'success' ? 'bg-green-50 border-l-4 border-green-500' : '',
                    type === 'error' ? 'bg-red-50 border-l-4 border-red-500' : '',
                    type === 'info' ? 'bg-blue-50 border-l-4 border-blue-500' : '',
                    type === 'warning' ? 'bg-yellow-50 border-l-4 border-yellow-500' : ''
                ]"
            >
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <svg v-if="type === 'success'" class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-if="type === 'error'" class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-if="type === 'info'" class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-if="type === 'warning'" class="h-6 w-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                
                <!-- Message -->
                <div class="flex-1">
                    <p 
                        :class="[
                            'text-sm font-medium',
                            type === 'success' ? 'text-green-800' : '',
                            type === 'error' ? 'text-red-800' : '',
                            type === 'info' ? 'text-blue-800' : '',
                            type === 'warning' ? 'text-yellow-800' : ''
                        ]"
                    >
                        {{ message }}
                    </p>
                </div>
                
                <!-- Close Button -->
                <button 
                    @click="close"
                    class="flex-shrink-0"
                >
                    <svg class="h-5 w-5 text-gray-500 hover:text-gray-700" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const type = ref('success');
const message = ref('');

const close = () => {
    show.value = false;
};

const displayFlash = () => {
    if (page.props.flash?.success) {
        type.value = 'success';
        message.value = page.props.flash.success;
        show.value = true;
        setTimeout(close, 5000);
    } else if (page.props.flash?.error) {
        type.value = 'error';
        message.value = page.props.flash.error;
        show.value = true;
        setTimeout(close, 7000);
    } else if (page.props.flash?.info) {
        type.value = 'info';
        message.value = page.props.flash.info;
        show.value = true;
        setTimeout(close, 5000);
    } else if (page.props.flash?.warning) {
        type.value = 'warning';
        message.value = page.props.flash.warning;
        show.value = true;
        setTimeout(close, 6000);
    }
};

onMounted(displayFlash);
watch(() => page.props.flash, displayFlash, { deep: true });
</script>
