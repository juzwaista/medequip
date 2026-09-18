<template>
    <Transition name="fade">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden relative" @click.stop>
                <!-- Close Button -->
                <button @click="close" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-center text-white">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-2xl mb-4 backdrop-blur-md">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <h2 class="text-2xl font-extrabold mb-1">Welcome to Wholesale</h2>
                    <p class="text-blue-100 text-sm">You are now ready to {{ type === 'buyer' ? 'procure' : 'sell' }} in bulk.</p>
                </div>

                <!-- Content -->
                <div class="p-8">
                    <div v-if="type === 'buyer'" class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 mt-1"><div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">1</div></div>
                            <div>
                                <h4 class="font-bold text-gray-900">Look for Wholesale Badges</h4>
                                <p class="text-sm text-gray-500">Products with the wholesale badge offer significant discounts for bulk orders.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 mt-1"><div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">2</div></div>
                            <div>
                                <h4 class="font-bold text-gray-900">Meet Minimum Quantities</h4>
                                <p class="text-sm text-gray-500">Add the required minimum quantity to your cart to automatically unlock the discount.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 mt-1"><div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">3</div></div>
                            <div>
                                <h4 class="font-bold text-gray-900">Purchase Orders</h4>
                                <p class="text-sm text-gray-500">At checkout, select "Purchase Order" and upload your company's PO document. The seller will verify and approve.</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="type === 'distributor'" class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 mt-1"><div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">1</div></div>
                            <div>
                                <h4 class="font-bold text-gray-900">Set Wholesale Pricing</h4>
                                <p class="text-sm text-gray-500">Edit your products to add a lower Wholesale Price and a Minimum Quantity to attract corporate buyers.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 mt-1"><div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">2</div></div>
                            <div>
                                <h4 class="font-bold text-gray-900">Review Incoming POs</h4>
                                <p class="text-sm text-gray-500">Corporate orders will appear in your Orders tab as "Pending PO Verification". Review their uploaded document.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 mt-1"><div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">3</div></div>
                            <div>
                                <h4 class="font-bold text-gray-900">Approve & Fulfill</h4>
                                <p class="text-sm text-gray-500">Approve the PO to accept the terms. The order transitions to Approved, and inventory is automatically deducted.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between items-center">
                        <Link href="/guides/b2b" class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition">Read Full Guide</Link>
                        <button @click="close" class="bg-gray-900 text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-900/20">
                            Got it, let's go!
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    type: {
        type: String,
        required: true, // 'buyer' or 'distributor'
    }
});

const isOpen = ref(false);
const storageKey = `medequip_has_seen_${props.type}_onboarding_v1`;

onMounted(() => {
    // Check if they've seen it
    if (!localStorage.getItem(storageKey)) {
        // Small delay so it doesn't pop up instantly
        setTimeout(() => {
            isOpen.value = true;
        }, 1000);
    }
});

const close = () => {
    isOpen.value = false;
    localStorage.setItem(storageKey, 'true');
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-active .bg-white {
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-enter-from .bg-white {
    transform: scale(0.95) translateY(10px);
}
</style>
