<template>
    <Head title="Reset password · MedEquip" />
    <AuthLayout>
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Set a new password</h1>
        <p class="mt-1 text-ink-soft">Choose a strong password for your account.</p>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <TextInput
                v-model="form.email"
                label="Email"
                type="email"
                required
                autocomplete="username"
                :error="form.errors.email"
            />
            <TextInput
                v-model="form.password"
                label="New password"
                type="password"
                required
                autocomplete="new-password"
                :error="form.errors.password"
            />
            <TextInput
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                required
                autocomplete="new-password"
            />
            <BaseButton type="submit" :disabled="form.processing" class="w-full">
                {{ form.processing ? 'Saving…' : 'Reset password' }}
            </BaseButton>
        </form>

        <p class="mt-6 text-center">
            <Link href="/login" class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Back to sign in</Link>
        </p>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: String,
    email: String,
});

const form = useForm({
    token: props.token,
    email: props.email || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
