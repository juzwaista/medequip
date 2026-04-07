<template>
    <Head title="Complete Your Administrator Profile" />
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-black text-gray-900 tracking-tight">
                Welcome to MedEquip
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 font-medium">
                Complete your profile to join the administrative team.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-white py-10 px-6 shadow-2xl sm:rounded-3xl sm:px-12 border border-blue-50 relative overflow-hidden">
                <!-- Decorative element -->
                <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-blue-50 rounded-full"></div>
                
                <form @submit.prevent="submit" class="space-y-6 relative z-10">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">Email Address</label>
                        <input
                            type="text"
                            :value="email"
                            disabled
                            class="block w-full px-5 py-3.5 bg-gray-50 border-0 rounded-2xl text-gray-400 font-bold focus:ring-0 cursor-not-allowed"
                        />
                    </div>

                    <div>
                        <label for="name" class="block text-[10px] font-black uppercase text-gray-500 mb-2 tracking-widest">Full Name</label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter your professional name"
                            class="block w-full px-5 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-500 focus:bg-white focus:ring-0 transition-all font-bold placeholder:text-gray-300"
                        />
                        <span v-if="form.errors.name" class="text-xs text-red-600 mt-2 block">{{ form.errors.name }}</span>
                    </div>

                    <div>
                        <label for="password" class="block text-[10px] font-black uppercase text-gray-500 mb-2 tracking-widest">Choose Password</label>
                        <input
                            v-model="form.password"
                            id="password"
                            type="password"
                            required
                            placeholder="••••••••"
                            class="block w-full px-5 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-500 focus:bg-white focus:ring-0 transition-all font-bold placeholder:text-gray-300"
                        />
                        <span v-if="form.errors.password" class="text-xs text-red-600 mt-2 block">{{ form.errors.password }}</span>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-[10px] font-black uppercase text-gray-500 mb-2 tracking-widest">Confirm Password</label>
                        <input
                            v-model="form.password_confirmation"
                            id="password_confirmation"
                            type="password"
                            required
                            placeholder="••••••••"
                            class="block w-full px-5 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-500 focus:bg-white focus:ring-0 transition-all font-bold placeholder:text-gray-300"
                        />
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-2xl shadow-xl text-sm font-black text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all active:scale-95 disabled:opacity-50"
                        >
                             <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Complete Setup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
    email: props.email,
    token: props.token,
});

const submit = () => {
    form.post('/admin/setup', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
