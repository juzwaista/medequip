<template>
    <Head title="Reset password · MedEquip" />
    <div class="min-h-screen min-h-[100dvh] flex items-center justify-center px-4 py-10 bg-slate-50">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <a href="/products" class="inline-block">
                    <img :src="'/images/logo.png'" alt="MedEquip" class="h-12 w-auto mx-auto" />
                </a>
            </div>
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                <h1 class="text-2xl font-black text-gray-900">Set a new password</h1>
                <p class="text-gray-600 text-sm mt-2">Choose a strong password for your account.</p>

                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="username"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        />
                        <p v-if="form.errors.email" class="text-red-600 text-xs mt-1.5">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">New password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        />
                        <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold text-sm hover:bg-blue-700 disabled:opacity-60 transition"
                    >
                        {{ form.processing ? 'Saving…' : 'Reset password' }}
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    <a href="/login" class="text-blue-600 font-semibold hover:underline">Back to sign in</a>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

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
