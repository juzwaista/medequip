<template>
    <Head title="Forgot password · MedEquip" />
    <AuthLayout>
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Forgot your password?</h1>
        <p class="mt-1 text-ink-soft">Enter your email and we'll send you a link to choose a new one.</p>

        <AlertBanner v-if="status" variant="success" class="mt-5">{{ status }}</AlertBanner>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <TextInput
                v-model="form.email"
                label="Email"
                type="email"
                required
                autofocus
                autocomplete="username"
                :error="form.errors.email"
            />
            <BaseButton type="submit" :disabled="form.processing" class="w-full">
                {{ form.processing ? 'Sending…' : 'Email me a reset link' }}
            </BaseButton>
        </form>

        <p class="mt-6 text-center">
            <Link href="/login" class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Back to sign in</Link>
        </p>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>
