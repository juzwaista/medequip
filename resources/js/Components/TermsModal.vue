<template>
    <!-- Terms & Conditions Modal -->
    <div v-if="show" class="fixed inset-0 z-[100] bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Terms & Conditions</h2>
                    <p class="text-xs text-gray-500 mt-0.5">MedEquip Platform · Effective March 29, 2026</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="overflow-y-auto flex-1 p-6 text-sm text-gray-700 space-y-5 leading-relaxed" v-html="$page.props.terms_and_conditions">
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                <button @click="$emit('close')"
                    class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    role: { type: String, default: null }, // 'customer' | 'distributor' | null (shows both)
});
defineEmits(['close']);
</script>
