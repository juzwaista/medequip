<template>
    <div
        v-if="show"
        class="bg-amber-50 border-b border-amber-200 text-amber-950 px-3 sm:px-6 py-3 relative z-40"
        role="status"
    >
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0 flex items-start gap-2">
                <svg class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-amber-900">Confirm your email</p>
                    <p class="text-xs sm:text-sm text-amber-800/90 mt-1 leading-relaxed" v-html="roleBannerText"></p>
                    <p
                        v-if="$page.props.flash?.status === 'verification-link-sent'"
                        class="text-xs font-semibold text-emerald-800 mt-2 flex items-center gap-1.5"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Resent email successfully!
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:flex-shrink-0">
                <button
                    type="button"
                    :disabled="resendForm.processing || cooldown > 0"
                    class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-bold hover:bg-amber-700 disabled:opacity-60 transition shadow-sm min-w-[120px]"
                    @click="resend"
                >
                    <svg v-if="resendForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <span v-if="cooldown > 0">Wait {{ cooldown }}s</span>
                    <span v-else>{{ resendForm.processing ? 'Sending…' : 'Resend email' }}</span>
                </button>
                <Link
                    href="/settings"
                    class="text-sm font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-950"
                >
                    Account settings
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onUnmounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    needsTermsAcceptance: {
        type: Boolean,
        default: false,
    },
});

const email = computed(() => page.props.auth?.user?.email || '');
const role = computed(() => page.props.auth?.user?.role || 'customer');

const roleBannerText = computed(() => {
    switch (role.value) {
        case 'distributor':
            return 'You can browse now; you cannot **post products or manage orders** until you verify.';
        case 'courier':
            return 'You can browse now; you cannot **view routes or manage shipments** until you verify.';
        case 'admin':
        case 'super_admin':
            return 'You can browse now; you cannot **access administrative tools** until you verify.';
        default:
            return 'You can browse the catalog now; **checkout and wallet** stay locked until you verify.';
    }
});

const show = computed(() => {
    const u = page.props.auth?.user;
    if (!u || u.email_verified_at) {
        return false;
    }
    if (props.needsTermsAcceptance) {
        return false;
    }
    return true;
});

const resendForm = useForm({});
const cooldown = ref(0);
let timer = null;

function startCooldown() {
    cooldown.value = 60;
    timer = setInterval(() => {
        if (cooldown.value > 0) {
            cooldown.value--;
        } else {
            clearInterval(timer);
        }
    }, 1000);
}

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

function resend() {
    if (cooldown.value > 0) return;
    
    resendForm.post('/email/verification-notification', {
        preserveScroll: true,
        onSuccess: () => {
            startCooldown();
        }
    });
}
</script>
