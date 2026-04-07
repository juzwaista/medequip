<template>
    <Head title="Security Verification" />

    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
            <h2 class="text-center text-3xl font-black text-slate-900 tracking-tight">
                Security Verification
            </h2>
            <p class="mt-2 text-center text-sm text-slate-500 font-medium">
                We've sent a 6-digit code to your email to verify it's really you.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl shadow-slate-200 sm:rounded-3xl sm:px-10 border border-slate-100">
                <!-- Status/Error Alerts -->
                <div v-if="info" class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-2xl flex items-start gap-3">
                    <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm font-semibold text-blue-700 leading-tight">{{ info }}</p>
                </div>
                
                <div v-if="form.errors.otp" class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm font-semibold text-red-700 leading-tight">{{ form.errors.otp }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="otp" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">
                            Verification Code
                        </label>
                        <div class="flex justify-between gap-2 sm:gap-4">
                            <input
                                v-for="(digit, index) in 6"
                                :key="index"
                                :ref="el => (inputRefs[index] = el)"
                                type="text"
                                maxlength="1"
                                v-model="otpParts[index]"
                                @input="handleInput($event, index)"
                                @keydown.delete="handleDelete(index)"
                                class="w-full h-14 sm:h-16 text-center text-2xl font-black text-slate-900 bg-slate-50 border-2 border-slate-100 rounded-2xl focus:border-blue-600 focus:ring-0 transition-all uppercase"
                                autocomplete="off"
                                autofocus
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !isComplete"
                        class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-2xl shadow-sm text-base font-bold text-white bg-slate-900 hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-[0.98]"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Verify & Login
                    </button>
                </form>

                <div class="mt-8 flex flex-col items-center gap-4">
                    <p class="text-xs text-slate-400 font-medium tracking-tight">
                        Didn't receive the code?
                    </p>
                    <button
                        @click="resend"
                        :disabled="isResending || cooldown > 0"
                        class="text-sm font-bold text-blue-600 hover:text-blue-700 disabled:text-slate-400 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="cooldown > 0">Resend in {{ cooldown }}s</span>
                        <span v-else-if="isResending">Sending Code...</span>
                        <span v-else>Resend Code</span>
                    </button>
                </div>
            </div>

            <div class="mt-8 text-center px-6">
                <Link
                    href="/login"
                    class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Normal Login
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
    info: String,
});

const otpParts = ref(['', '', '', '', '', '']);
const inputRefs = ref([]);
const cooldown = ref(60);
const isResending = ref(false);

const form = useForm({
    otp: '',
});

const isComplete = computed(() => {
    return otpParts.value.every(part => part !== '');
});

const handleInput = (e, index) => {
    const val = e.target.value;
    // Allow only alphanumeric (case insensitive)
    if (!/^[a-zA-Z0-9]$/.test(val)) {
        otpParts.value[index] = '';
        return;
    }

    otpParts.value[index] = val.toUpperCase();
    
    // Move to next input
    if (index < 5 && val) {
        inputRefs.value[index + 1]?.focus();
    }
};

const handleDelete = (index) => {
    if (!otpParts.value[index] && index > 0) {
        inputRefs.value[index - 1]?.focus();
    }
};

const submit = () => {
    form.otp = otpParts.value.join('');
    form.post(route('admin.otp.submit'), {
        onError: () => {
             // Optional: reset parts on error
             // otpParts.value = ['', '', '', '', '', ''];
             // inputRefs.value[0]?.focus();
        }
    });
};

const resend = () => {
    isResending.value = true;
    router.post(route('admin.otp.resend'), {}, {
        onFinish: () => {
            isResending.value = false;
            cooldown.value = 60;
            startCooldown();
        }
    });
};

const startCooldown = () => {
    const timer = setInterval(() => {
        if (cooldown.value > 0) {
            cooldown.value--;
        } else {
            clearInterval(timer);
        }
    }, 1000);
};

onMounted(() => {
    startCooldown();
});

// Watch for pasting
watch(() => otpParts.value[0], (newVal) => {
    if (newVal.length > 1) {
        const pasted = newVal.substring(0, 6).toUpperCase().split('');
        pasted.forEach((char, i) => {
            if (i < 6) otpParts.value[i] = char;
        });
        const nextIdx = Math.min(pasted.length, 5);
        inputRefs.value[nextIdx]?.focus();
    }
}, { deep: true });

</script>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
