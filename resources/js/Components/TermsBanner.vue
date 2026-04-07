<template>
    <!-- Terms Update Banner for existing users who haven't accepted current T&C -->
    <div v-if="needsAcceptance && !dismissed" class="fixed inset-0 z-[90] bg-black/70 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl shadow-2xl overflow-hidden">
            <!-- Top accent -->
            <div class="h-1.5 bg-gradient-to-r from-blue-600 to-cyan-500"></div>

            <div class="p-6">
                <div class="flex items-start gap-4 mb-5">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-gray-900">Updated Terms & Conditions</h2>
                        <p class="text-sm text-gray-500 mt-0.5">
                            We've updated our platform terms as of <strong>March 29, 2026</strong>. A review and acceptance is required to continue.
                        </p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
                    <p class="text-xs font-bold text-amber-800 mb-1 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        What's new in this update
                    </p>
                    <ul class="text-xs text-amber-700 space-y-1 list-disc list-inside">
                        <li>Updated Distributor verification requirements</li>
                        <li>Clarified COD payment and remittance policies</li>
                        <li>New platform fee structures for Distributors</li>
                        <li>Enhanced data privacy disclosures (RA 10173)</li>
                    </ul>
                </div>

                <div ref="termsContainer" @scroll="onScroll" class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4 max-h-40 overflow-y-auto text-xs text-gray-600 leading-relaxed">
                    <p class="font-bold text-gray-800 mb-2">Summary of Terms</p>
                    <p class="mb-2">By accepting, you agree to MedEquip's Terms and Conditions including all applicable role-specific terms for your account type (<strong>{{ userRole }}</strong>).</p>
                    <p class="mb-2"><strong>For Customers:</strong> You agree to our ordering, delivery, refund, and wallet policies. Medical devices requiring prescription must be ordered with valid documentation.</p>
                    <p><strong>For Distributors:</strong> You must maintain valid FDA LTO, business permits, and all required licenses. Platform fees apply per completed order. Violations may result in account suspension.</p>
                    <button @click="showFull = true" class="text-blue-600 hover:underline font-semibold mt-2 block">Read full Terms & Conditions →</button>
                    <!-- Small spacer at the bottom to ensure scroll threshold is clearly crossed -->
                    <div class="h-2"></div>
                </div>

                <div class="flex items-center gap-2 mb-5">
                    <input type="checkbox" id="acceptCheck" v-model="hasChecked" :disabled="!hasScrolledToBottom" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition-opacity" :class="{'opacity-50 cursor-not-allowed': !hasScrolledToBottom}">
                    <label for="acceptCheck" class="text-sm text-gray-700 transition-opacity" :class="{'opacity-50': !hasScrolledToBottom}">I have read and accept the Terms & Conditions</label>
                </div>

                <!-- Full T&C Modal -->
                <TermsModal :show="showFull" :role="userRole" @close="showFull = false" />

                <div class="flex flex-col gap-3">
                    <button @click="accept" :disabled="processing || !hasChecked"
                        class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <svg v-if="processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <svg v-if="!processing" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        I Accept the Terms &amp; Conditions
                    </button>
                    <button @click="logout" class="w-full text-gray-500 font-semibold py-2 text-sm hover:text-gray-800 transition">
                        Decline & Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import TermsModal from '@/Components/TermsModal.vue';

const page = usePage();

const props = defineProps({
    needsAcceptance: { type: Boolean, default: false },
    userRole: { type: String, default: 'customer' },
});

const dismissed = ref(false);
const processing = ref(false);
const showFull = ref(false);

const termsContainer = ref(null);
const hasScrolledToBottom = ref(false);
const hasChecked = ref(false);

const onScroll = (e) => {
    const el = e.target;
    // Adding a 10px buffer for precision differences on various browsers
    if (el.scrollHeight - el.scrollTop <= el.clientHeight + 10) {
        hasScrolledToBottom.value = true;
    }
};

onMounted(() => {
    setTimeout(() => {
        if (termsContainer.value) {
            // Check if contents are short enough that no scrolling is needed
            if (termsContainer.value.scrollHeight <= termsContainer.value.clientHeight + 10) {
                hasScrolledToBottom.value = true;
            }
        }
    }, 100);
});

const accept = () => {
    if (!hasChecked.value) return;
    processing.value = true;
    router.post('/terms/accept', {
        terms_accepted: true,
        redirect_to: window.location.pathname + window.location.search,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            dismissed.value = true;
            processing.value = false;
        },
        onError: (errors) => {
            processing.value = false;
            // 419 expired often returns an empty object or a generic message. Let's just reload.
            if (!errors || Object.keys(errors).length === 0) {
                window.location.reload();
            }
        },
    });
};

const logout = () => {
    router.post('/logout');
};
</script>
