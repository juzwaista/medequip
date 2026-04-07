<template>
    <Head title="Forgot password · MedEquip" />
    <div class="min-h-screen min-h-[100dvh] flex items-center justify-center px-4 py-10 bg-slate-50">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <a href="/products" class="inline-block">
                    <img :src="'/images/logo.png'" alt="MedEquip" class="h-12 w-auto mx-auto" />
                </a>
            </div>
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                <h1 class="text-2xl font-black text-gray-900">Forgot password</h1>
                <p class="text-gray-600 text-sm mt-2">
                    Enter your email and we’ll send a link to choose a new password.
                </p>

                <div
                    v-if="status"
                    class="mt-4 p-3 rounded-xl text-sm text-emerald-800 bg-emerald-50 border border-emerald-200"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        />
                        <p v-if="form.errors.email" class="text-red-600 text-xs mt-1.5">{{ form.errors.email }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold text-sm hover:bg-blue-700 disabled:opacity-60 transition"
                    >
                        {{ form.processing ? 'Sending…' : 'Email reset link' }}
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
