<template>
    <Head title="Login · MedEquip" />
<div class="min-h-screen min-h-[100dvh] flex min-w-0 overflow-x-hidden">
    <!-- Left: Brand Panel (hidden on mobile) -->
    <div class="hidden lg:flex lg:w-2/5 xl:w-2/5 bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 flex-col justify-between p-12 relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-cyan-400/20 rounded-full blur-2xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-800/20 rounded-full blur-3xl"></div>
        </div>

        <!-- Logo -->
        <div class="relative z-10">
            <a href="/products" class="inline-flex items-center gap-3">
                <img :src="'/images/logo.png'" alt="MedEquip" class="h-24 sm:h-28 md:h-24 lg:h-28 w-auto brightness-0 invert" />
            </a>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 space-y-6">
            

            <h1 class="text-4xl xl:text-5xl font-black text-white leading-tight">
                Access your<br>
                <span class="text-cyan-300">MedEquip account</span>
            </h1>

            <p class="text-blue-100 text-lg leading-relaxed max-w-sm">
                Continue managing your clinic’s supply needs with secure and reliable access to your account.
            </p>

            <!-- Trust badges -->
            <div class="grid grid-cols-3 gap-4 pt-4">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20 text-center">
                    <p class="text-2xl font-black text-white">FDA</p>
                    <p class="text-xs text-blue-200 mt-1">Verified sellers</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20 text-center">
                    <p class="text-2xl font-black text-white">100%</p>
                    <p class="text-xs text-blue-200 mt-1">Secure checkout</p>
                </div>

                <!-- Highlighted / Important Badge -->
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20 text-center">
                    <p class="text-2xl font-black text-white">Fast</p>
                    <p class="text-xs text-blue-200 mt-1">Delivery Process</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="relative z-10 text-blue-200 text-xs">&copy; 2026 MedEquip Platform. Cavite, Philippines.</p>
    </div>

        <!-- Right: Login Form -->
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 py-8 sm:py-12 bg-slate-50 min-w-0">
            <div class="w-full max-w-md">
                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-8">
                    <a href="/products">
                        <img :src="'/images/logo.png'" alt="MedEquip" class="h-12 w-auto mx-auto" />
                    </a>
                </div>

                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                    <div class="mb-8">
                        <h2 class="text-2xl font-black text-gray-900">Welcome back!</h2>
                        <p class="text-gray-500 text-sm mt-1.5">Let's get your clinic stocked up.</p>
                    </div>

                    <div v-if="status" class="mb-5 p-3.5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2">
                        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ status }}
                    </div>

                    <div v-if="error || form.errors.login" class="mb-5 p-3.5 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
                        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ form.errors.login || error || 'These credentials do not match our records.' }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5 text-gray-700">Email or username</label>
                            <input
                                type="text"
                                v-model="form.login"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="you@example.com or clinic_user"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5 text-gray-700">Password</label>
                            <div class="relative">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300 pr-12"
                                >
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                                <span class="text-sm text-gray-600">Remember me</span>
                            </label>
                            <a href="/forgot-password" class="text-sm text-blue-600 hover:text-blue-700 font-semibold hover:underline transition">
                                Forgot password?
                            </a>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-blue-600 text-white py-3.5 rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-sm hover:shadow-md disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ form.processing ? 'Signing in…' : 'Sign In' }}
                        </button>
                    </form>

                    <p class="text-sm text-center mt-6 text-gray-500">
                        New to MedEquip?
                        <Link href="/register" class="text-blue-600 hover:underline font-bold ml-1">
                            Create an account!
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
    error: String,
});

const showPassword = ref(false);

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
