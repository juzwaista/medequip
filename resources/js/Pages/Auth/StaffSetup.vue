<template>
    <Head title="Complete your staff profile · MedEquip" />
    <AuthLayout headline="Welcome to the team." blurb="Finish setting up your account to get started.">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Welcome to MedEquip</h1>
        <p class="mt-1 text-ink-soft">Complete your profile to join the staff team.</p>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <TextInput label="Email address" type="text" :model-value="email" disabled />
            <TextInput
                v-model="form.username"
                label="Username"
                type="text"
                required
                placeholder="Choose a unique username"
                :error="form.errors.username"
            />
            <TextInput
                v-model="form.password"
                label="Choose a password"
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
                {{ form.processing ? 'Saving…' : 'Complete setup' }}
            </BaseButton>
        </form>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    username: '',
    password: '',
    password_confirmation: '',
    email: props.email,
    token: props.token,
});

const submit = () => {
    form.post('/staff/setup', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
