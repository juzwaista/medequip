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
                    <p class="text-xs sm:text-sm text-amber-800/90 mt-0.5 leading-relaxed">
                        We sent a link to <span class="font-semibold break-all">{{ email }}</span>.
                        You can browse the catalog now; <strong>checkout and wallet</strong> stay locked until you verify.
                    </p>
                    <p
                        v-if="$page.props.flash?.status === 'verification-link-sent'"
                        class="text-xs font-semibold text-emerald-800 mt-2"
                    >
                        A fresh link was sent — check your inbox (or your log file if using local <code class="text-[11px] bg-amber-100/80 px-1 rounded">MAIL_MAILER=log</code>).
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:flex-shrink-0">
                <button
                    type="button"
                    :disabled="resendForm.processing"
                    class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-bold hover:bg-amber-700 disabled:opacity-60 transition shadow-sm"
                    @click="resend"
                >
                    <svg v-if="resendForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ resendForm.processing ? 'Sending…' : 'Resend email' }}
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
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    needsTermsAcceptance: {
        type: Boolean,
        default: false,
    },
});

const email = computed(() => page.props.auth?.user?.email || '');

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

function resend() {
    resendForm.post('/email/verification-notification', {
        preserveScroll: true,
    });
}
</script>
