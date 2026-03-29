<template>
    <Head title="Create Account · MedEquip" />
    <TermsModal :show="showTermsModal" :role="form.role" @close="showTermsModal = false" />

    <div class="min-h-screen flex">
        <!-- Left: Brand Panel (Fixed) -->
        <div class="hidden lg:flex lg:w-[40%] xl:w-[42%] bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 flex-col justify-between p-10 relative overflow-hidden flex-shrink-0 sticky top-0 h-screen">
            <!-- Background decorations -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-72 h-72 bg-cyan-400/20 rounded-full blur-2xl"></div>
            </div>

            <!-- Logo -->
            <div class="relative z-10">
                <a href="/products" class="inline-flex items-center gap-3">
                    <img 
                        :src="'/images/logo.png'" 
                        alt="MedEquip" 
                        class="h-8 sm:h-10 md:h-14 lg:h-20 xl:h-24 w-auto brightness-0 invert transition-all duration-300"
                    />
                </a>
            </div>

            <!-- Main Content -->
            <div class="relative z-10 space-y-5">
                <h1 class="text-3xl xl:text-4xl font-black text-white leading-tight">
                    Stock your clinic with ease.<br>
                    <span class="text-cyan-300">Get started today.</span>
                </h1>
                <p class="text-blue-100 leading-relaxed">
                    Browse and order trusted medical equipment and essential supplies from verified local suppliers—fast, simple, and reliable.
                </p>
                <ul class="space-y-3 pt-2">
                    <li v-for="item in ['Verified local distributors', 'Secure payments', 'Convinient ordering', 'Fast, direct delivery']" :key="item" class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-emerald-400/30 border border-emerald-400/50 flex items-center justify-center">
                            <svg class="h-3 w-3 text-emerald-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        {{ item }}
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-blue-200 text-xs">&copy; 2026 MedEquip Platform. Cavite, Philippines.</p>
        </div>

        <!-- Right: Registration Form (Scrollable) -->
        <div class="flex-1 overflow-y-auto bg-slate-50 flex justify-center py-10 px-4 sm:px-8">
            <div class="w-full max-w-xl">
                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-6">
                    <a href="/products">
                        <img :src="'/images/logo.png'" alt="MedEquip" class="h-10 w-auto mx-auto" />
                    </a>
                </div>

                <!-- Registration Card -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                    <div class="mb-7">
                        <h2 class="text-2xl font-black text-gray-900">Get started</h2>
                        <p class="text-gray-500 text-sm mt-1.5">Create your free MedEquip account</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Personal Info -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2">Personal Information</h3>

                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Full Name</label>
                                <input type="text" v-model="form.name" required autofocus placeholder="Your full name"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                <p v-if="form.errors.name" class="text-red-600 text-xs mt-1.5">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Email Address</label>
                                <input type="email" v-model="form.email" required placeholder="you@example.com"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                <p v-if="form.errors.email" class="text-red-600 text-xs mt-1.5">{{ form.errors.email }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Password</label>
                                    <div class="relative">
                                        <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required placeholder="••••••••"
                                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Confirm Password</label>
                                    <input :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" required placeholder="••••••••"
                                        class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                </div>
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2">Account Type</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label :class="form.role === 'customer' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50'"
                                    class="flex items-start gap-3 cursor-pointer p-4 rounded-2xl border-2 transition-all">
                                    <input type="radio" v-model="form.role" value="customer" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <span class="font-bold text-gray-900 text-sm">Customer</span>
                                        <p class="text-xs text-gray-500 mt-0.5">Purchase medical supplies & equipment</p>
                                    </div>
                                </label>
                                <label :class="form.role === 'distributor' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50'"
                                    class="flex items-start gap-3 cursor-pointer p-4 rounded-2xl border-2 transition-all">
                                    <input type="radio" v-model="form.role" value="distributor" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <span class="font-bold text-gray-900 text-sm">Distributor</span>
                                        <p class="text-xs text-gray-500 mt-0.5">Sell products & manage inventory</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.role" class="text-red-600 text-xs">{{ form.errors.role }}</p>
                        </div>

                        <!-- Delivery Address (customers only) -->
                        <div v-if="form.role === 'customer'" class="space-y-4">
                            <!-- city, barangay, address fields -->
                            <!-- (your existing code stays here, unchanged) -->
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-2.5">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="form.terms_accepted" 
                                    class="mt-0.5 h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0"
                                >
                                <div class="text-xs leading-snug">
                                    <span class="text-gray-700">
                                        I agree to the
                                        <button type="button" @click="showTermsModal = true" class="text-blue-600 hover:underline font-semibold">
                                            Terms and Conditions
                                        </button>
                                        of MedEquip, including the
                                        <span v-if="form.role === 'customer'" class="font-semibold text-gray-800">Customer Terms</span>
                                        <span v-else-if="form.role === 'distributor'" class="font-semibold text-gray-800">Distributor Terms</span>
                                        <span v-else class="font-semibold text-gray-800">Platform Terms</span>.
                                    </span>
                                </div>
                            </label>

                            <p v-if="form.errors.terms_accepted" class="text-red-600 text-[11px] mt-1 ml-5">
                                {{ form.errors.terms_accepted }}
                            </p>
                        </div>

                        <button type="submit" :disabled="form.processing || !form.terms_accepted"
                            class="w-full bg-blue-600 text-white py-3.5 rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-sm hover:shadow-md disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ form.processing ? 'Creating account…' : 'Create Account' }}
                        </button>
                    </form>

                    <p class="text-sm text-center mt-5 text-gray-500">
                        Already have an account?
                        <Link href="/login" class="text-blue-600 hover:underline font-bold ml-1">Sign in</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TermsModal from '@/Components/TermsModal.vue';

const props = defineProps({
    cities: Object,
    barangays: Object,
});

const showPassword = ref(false);
const showTermsModal = ref(false);
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'customer',
    contact_number: '',
    address_line: '',
    city: '',
    barangay: '',
    terms_accepted: false,
});

const sanitizeContactNumber = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length > 11) val = val.slice(0, 11);
    form.contact_number = val;
};

const availableBarangays = computed(() => {
    if (!form.city || !props.barangays) return [];
    return props.barangays[form.city] || [];
});

const showManualBarangay = computed(() => {
    return selectedBarangay.value === 'other' ||
           (form.city && availableBarangays.value.length === 0);
});

const onCityChange = () => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    if (form.city && props.cities[form.city]) {
        zipCode.value = props.cities[form.city].zip;
    } else {
        zipCode.value = '';
    }
    setTimeout(() => {
        if (availableBarangays.value.length === 0 && form.city) {
            selectedBarangay.value = 'other';
        }
    }, 50);
};

watch([selectedBarangay, manualBarangay], () => {
    form.barangay = showManualBarangay.value && manualBarangay.value.length > 0
        ? manualBarangay.value
        : (selectedBarangay.value !== 'other' ? selectedBarangay.value : '');
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>