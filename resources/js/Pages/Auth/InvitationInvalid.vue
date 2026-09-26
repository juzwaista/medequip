<template>
    <Head title="Invalid invitation · MedEquip" />
    <AuthLayout headline="This invitation can't be used." blurb="Invitation links work once and expire. Ask the person who invited you to send a new one.">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Invitation problem</h1>
        <AlertBanner variant="error" class="mt-5">
            {{ message || 'This invitation link is invalid, expired, or has already been used.' }}
        </AlertBanner>
        <BaseButton href="/login" class="mt-6 w-full">Back to sign in</BaseButton>
        <p class="mt-4 text-center text-ink-soft">Need help? Contact platform support.</p>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>
