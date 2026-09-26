<template>
    <Head title="Sign in · MedEquip" />
    <AuthLayout
        headline="Welcome back."
        blurb="Pick up where you left off: reorder supplies, follow your deliveries or manage your shop."
        :points="[]"
    >
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Sign in</h1>
        <p class="mt-1 text-ink-soft">Buyers, sellers and couriers all sign in here with an email or username.</p>

        <AlertBanner v-if="status" variant="success" class="mt-5">{{ status }}</AlertBanner>
        <AlertBanner v-if="error || form.errors.login" variant="error" class="mt-5">
            {{ form.errors.login || error || 'These credentials do not match our records.' }}        </AlertBanner>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <TextInput
                v-model="form.login"
                label="Email or username"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
            />

            <TextInput
                v-model="form.password"
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                input-class="pr-11"
                :error="form.errors.password"
                :hint="capsLock ? 'Caps Lock is on.' : ''"
                @keydown="checkCapsLock"
                @keyup="checkCapsLock"
                @blur="capsLock = false"
            >
                <template #end>
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="rounded-control p-1.5 text-ink-faint hover:text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </template>
            </TextInput>

            <div class="flex items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" v-model="form.remember" class="h-4 w-4 rounded-control border-line accent-brand">
                    <span class="text-ink-soft">Remember me</span>
                </label>
                <Link href="/forgot-password" class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">
                    Forgot password?
                </Link>
            </div>

            <BaseButton type="submit" :disabled="form.processing" class="w-full">
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </BaseButton>
        </form>

        <div class="mt-6 space-y-2 border-t border-line pt-5 text-center text-ink-soft">
            <p>
                New to MedEquip?
                <Link href="/register" class="ml-1 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Create an account</Link>
            </p>
            <p>
                Just looking?
                <Link href="/products" class="ml-1 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Browse products without signing in</Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
    error: String,
});

const showPassword = ref(false);
const capsLock = ref(false);

const checkCapsLock = (e) => {
    capsLock.value = !!e.getModifierState?.('CapsLock');
};

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
