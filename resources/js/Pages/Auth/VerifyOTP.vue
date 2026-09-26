<template>
    <Head title="Security verification · MedEquip" />
    <AuthLayout headline="One more step." blurb="This extra check keeps administrator accounts safe.">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Security verification</h1>
        <p class="mt-1 text-ink-soft">We sent a 6-digit code to your email to confirm it's really you.</p>

        <AlertBanner v-if="info" variant="info" class="mt-5">{{ info }}</AlertBanner>
        <AlertBanner v-if="form.errors.otp" variant="error" class="mt-5">{{ form.errors.otp }}</AlertBanner>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            <div role="group" aria-labelledby="otp-label">
                <p id="otp-label" class="mb-2 text-sm font-medium text-ink">Verification code</p>
                <div class="flex justify-between gap-2 sm:gap-3">
                    <input
                        v-for="(digit, index) in 6"
                        :key="index"
                        :ref="el => (inputRefs[index] = el)"
                        type="text"
                        maxlength="1"
                        v-model="otpParts[index]"
                        @input="handleInput($event, index)"
                        @keydown.delete="handleDelete(index)"
                        :aria-label="`Digit ${index + 1} of 6`"
                        class="h-14 w-full rounded-control border border-line bg-white text-center text-2xl font-semibold uppercase tabular-nums text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                        autocomplete="off"
                    />
                </div>
            </div>

            <BaseButton type="submit" :disabled="form.processing || !isComplete" class="w-full">
                {{ form.processing ? 'Verifying…' : 'Verify and sign in' }}
            </BaseButton>
        </form>

        <div class="mt-6 flex flex-col items-center gap-1 text-center">
            <p class="text-ink-soft">Didn't receive the code?</p>
            <button
                @click="resend"
                :disabled="isResending || cooldown > 0"
                class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2 disabled:cursor-not-allowed disabled:text-ink-faint disabled:no-underline"
            >
                <span v-if="cooldown > 0">Resend in {{ cooldown }}s</span>
                <span v-else-if="isResending">Sending code…</span>
                <span v-else>Resend code</span>
            </button>
        </div>

        <template #below>
            <div class="mt-6 text-center">
                <button @click="logout" class="text-sm font-medium text-ink-soft hover:text-danger">
                    Sign out of this session
                </button>
            </div>
        </template>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';

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
    // Allow only alphanumeric
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
    form.post('/admin/otp-verify', {
        onError: () => {
        }
    });
};

const resend = () => {
    isResending.value = true;
    router.post('/admin/otp-resend', {}, {
        onFinish: () => {
            isResending.value = false;
            cooldown.value = 60;
            startCooldown();
        }
    });
};

const logout = () => {
    router.post('/logout');
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
