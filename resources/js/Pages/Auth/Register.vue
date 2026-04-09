<template>
    <Head title="Create Account · MedEquip" />
    <TermsModal :show="showTermsModal" :role="form.role" @close="showTermsModal = false" />

    <div class="min-h-screen min-h-[100dvh] flex min-w-0 overflow-x-hidden">
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
                    <li v-for="item in ['Verified local distributors', 'Secure payments', 'Convenient ordering', 'Fast, direct delivery']" :key="item" class="flex items-center gap-3 text-sm text-blue-100">
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
                        <!-- Account -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2">Account</h3>

                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Username <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    v-model="form.username"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="letters, numbers, underscore (e.g. cavite_clinic)"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300"
                                >
                                <p class="text-xs text-gray-500 mt-1">Sign-in name. 4–20 characters: A–Z, 0–9, _ (stored lowercase).</p>
                                <p
                                    v-if="usernameHint.state !== 'idle' && usernameHint.state !== 'loading'"
                                    class="text-xs mt-1.5 font-medium flex items-center gap-1.5"
                                    :class="{
                                        'text-emerald-600': usernameHint.state === 'available',
                                        'text-red-600': usernameHint.state === 'taken' || usernameHint.state === 'invalid',
                                    }"
                                >
                                    <span v-if="usernameHint.state === 'available'" class="inline-flex h-4 w-4 rounded-full bg-emerald-100 text-emerald-700 items-center justify-center text-[10px]">✓</span>
                                    <span v-else-if="usernameHint.state === 'taken'" class="inline-flex h-4 w-4 rounded-full bg-red-100 text-red-700 items-center justify-center text-[10px]">×</span>
                                    {{ usernameHint.message }}
                                </p>
                                <p v-else-if="usernameHint.state === 'loading'" class="text-xs text-gray-500 mt-1.5">Checking availability…</p>
                                <p v-if="form.errors.username" class="text-red-600 text-xs mt-1.5">{{ form.errors.username }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" v-model="form.email" required placeholder="you@example.com"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                <p v-if="form.errors.email" class="text-red-600 text-xs mt-1.5">{{ form.errors.email }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required placeholder="••••••••"
                                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
                                    <ul v-if="form.password.length > 0" class="mt-2 space-y-1 text-xs text-gray-600">
                                        <li class="flex items-center gap-2" :class="pwdRules.len ? 'text-emerald-700' : ''">
                                            <span class="w-3.5 text-center">{{ pwdRules.len ? '✓' : '○' }}</span> At least 10 characters
                                        </li>
                                        <li class="flex items-center gap-2" :class="pwdRules.upper ? 'text-emerald-700' : ''">
                                            <span class="w-3.5 text-center">{{ pwdRules.upper ? '✓' : '○' }}</span> One uppercase letter
                                        </li>
                                        <li class="flex items-center gap-2" :class="pwdRules.lower ? 'text-emerald-700' : ''">
                                            <span class="w-3.5 text-center">{{ pwdRules.lower ? '✓' : '○' }}</span> One lowercase letter
                                        </li>
                                        <li class="flex items-center gap-2" :class="pwdRules.num ? 'text-emerald-700' : ''">
                                            <span class="w-3.5 text-center">{{ pwdRules.num ? '✓' : '○' }}</span> One number
                                        </li>
                                        <li class="flex items-center gap-2" :class="pwdRules.sym ? 'text-emerald-700' : ''">
                                            <span class="w-3.5 text-center">{{ pwdRules.sym ? '✓' : '○' }}</span> One symbol (e.g. !@#$)
                                        </li>
                                    </ul>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                    <input :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" required placeholder="••••••••"
                                        class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Contact Number <span class="text-red-500">*</span></label>
                                <input v-model="form.contact_number" @input="sanitizeContactNumber" type="tel" required inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" placeholder="09XX XXX XXXX"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                <p class="text-[10px] text-gray-500 mt-1">11 digits starting with 09 (e.g. 09123456789)</p>
                                <p v-if="form.errors.contact_number" class="text-red-600 text-xs mt-1.5">{{ form.errors.contact_number }}</p>
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
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2">Delivery Address</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">City / Municipality <span class="text-red-500">*</span></label>
                                    <select v-model="selectedCity" @change="onCityChange" required
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all bg-white text-sm">
                                        <option value="">Select city</option>
                                        <option v-for="(data, city) in cities" :key="city" :value="city">{{ city }}</option>
                                    </select>
                                    <p v-if="form.errors.city" class="text-red-600 text-xs mt-1.5">{{ form.errors.city }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barangay <span class="text-red-500">*</span></label>
                                    <select v-if="availableBarangays.length > 0" v-model="selectedBarangay" required
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all bg-white text-sm">
                                        <option value="">Select barangay</option>
                                        <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">{{ brgy }}</option>
                                        <option value="other">Other (type manually)</option>
                                    </select>
                                    <input v-else v-model="manualBarangay" type="text" required placeholder="Enter barangay name"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-sm placeholder-gray-300" />
                                    <p v-if="form.errors.barangay" class="text-red-600 text-xs mt-1.5">{{ form.errors.barangay }}</p>
                                </div>
                            </div>

                            <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barangay Name <span class="text-red-500">*</span></label>
                                <input v-model="manualBarangay" type="text" required placeholder="Type your barangay name"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-sm placeholder-gray-300" />
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Street Address <span class="text-red-500">*</span></label>
                                <input v-model="form.address_line" type="text" required placeholder="e.g., Blk 5 Lot 10 Sampaguita St."
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-sm placeholder-gray-300" />
                                <p v-if="form.errors.address_line" class="text-red-600 text-xs mt-1.5">{{ form.errors.address_line }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Zip Code <span class="text-red-500">*</span></label>
                                    <input v-model="zipCode" type="text" readonly placeholder="Auto-filled"
                                        class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl bg-gray-50 text-gray-500 text-sm" />
                                </div>
                            </div>
                            
                            <!-- Address Pin Location -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center gap-2">
                                    Pin Exact Location <span class="text-red-500">*</span>
                                    <span class="text-xs font-normal text-gray-400 font-mono italic">(Required for accurate delivery)</span>
                                </label>
                                <p class="text-[11px] text-gray-500 mb-2 leading-relaxed">
                                    Ensures hassle-free delivery or pickup. Click the button to use your GPS location, or drag the map.
                                </p>
                                <MapPicker 
                                    v-model:lat="form.latitude" 
                                    v-model:lng="form.longitude"
                                    :geocodeQuery="geocodeQuery"
                                    height="200px"
                                    @update:address="onMapAddressPicked"
                                />
                                <!-- Detected location banner -->
                                <transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <p v-if="detectedLocation" class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                        {{ detectedLocation }}
                                    </p>
                                </transition>
                                <p v-if="form.errors.latitude" class="text-red-600 text-xs mt-1.5">{{ form.errors.latitude }}</p>
                            </div>
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
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TermsModal from '@/Components/TermsModal.vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    cities: Object,
    barangays: Object,
});

const showPassword = ref(false);
const showTermsModal = ref(false);
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

// For map ↔ form sync
const geocodeQuery = ref(null);
const detectedLocation = ref('');
let detectedTimer = null;
let isProgrammaticChange = false;

const usernameHint = reactive({
    state: 'idle',
    message: '',
});

let usernameDebounce = null;
let usernameAbort = null;

const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'customer',
    contact_number: '',
    address_line: '',
    city: '',
    barangay: '',
    latitude: null,
    longitude: null,
    terms_accepted: false,
});

const sanitizeContactNumber = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length > 11) val = val.slice(0, 11);
    form.contact_number = val;
};

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

// ─── Fuzzy match (same as Addresses page) ────────────────────────────────────
const normalize = (str) =>
    String(str)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

const fuzzyMatch = (input, list) => {
    if (!input || !list || !list.length) return null;
    const normalInput = normalize(input);
    if (!normalInput) return null;
    const exact = list.find(item => normalize(item) === normalInput);
    if (exact) return exact;
    const startsWith = list.find(item => normalize(item).startsWith(normalInput + ' ') || normalize(item) === normalInput);
    if (startsWith) return startsWith;
    const superString = list.find(item => normalInput.includes(normalize(item)));
    if (superString) return superString;
    const subString = list.find(item => normalize(item).includes(normalInput));
    if (subString) return subString;
    return null;
};

// ─── Internal city-change helper ─────────────────────────────────────────────
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    form.city = city || '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
        if ((props.barangays?.[city] || []).length === 0) {
            selectedBarangay.value = 'other';
        }
    } else {
        zipCode.value = '';
    }
};

// ─── User changes city dropdown ───────────────────────────────────────────────
const onCityChange = () => {
    _applyCityChange(selectedCity.value);
    if (!isProgrammaticChange && selectedCity.value) {
        geocodeQuery.value = `${selectedCity.value}, Cavite, Philippines`;
    }
};

// ─── User changes barangay — pan map ─────────────────────────────────────────
watch(selectedBarangay, (brgy) => {
    if (!isProgrammaticChange && brgy && brgy !== 'other' && selectedCity.value) {
        geocodeQuery.value = `Barangay ${brgy}, ${selectedCity.value}, Cavite, Philippines`;
    }
});

watch([selectedCity, selectedBarangay, manualBarangay, zipCode], () => {
    form.city = selectedCity.value || '';
    form.barangay = selectedBarangay.value === 'other'
        ? (manualBarangay.value || '')
        : (selectedBarangay.value || '');
});

// ─── Map pin → form (same logic as Addresses/Index.vue) ──────────────────────
const onMapAddressPicked = ({ city, barangay }) => {
    if (!city && !barangay) return;

    isProgrammaticChange = true;

    const cityKeys = Object.keys(props.cities || {});
    const matchedCity = fuzzyMatch(city, cityKeys);
    let matchedBrgy = null;

    if (matchedCity) {
        selectedCity.value = matchedCity;
        _applyCityChange(matchedCity);

        if (barangay) {
            const brgys = props.barangays?.[matchedCity] || [];
            matchedBrgy = fuzzyMatch(barangay, brgys);
            if (matchedBrgy) {
                selectedBarangay.value = matchedBrgy;
            } else {
                selectedBarangay.value = 'other';
                manualBarangay.value = barangay;
            }
        }
    }

    const cityLabel = matchedCity || city || '';
    const brgyLabel = matchedBrgy || (barangay ? `${barangay} (unmatched)` : '');
    if (cityLabel) {
        detectedLocation.value = `Detected: ${cityLabel}${brgyLabel ? ' · Brgy. ' + brgyLabel : ''}`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }

    isProgrammaticChange = false;
};

const pwd = computed(() => form.password || '');
const pwdRules = computed(() => ({
    len: pwd.value.length >= 10,
    upper: /[A-Z]/.test(pwd.value),
    lower: /[a-z]/.test(pwd.value),
    num: /[0-9]/.test(pwd.value),
    sym: /[^A-Za-z0-9]/.test(pwd.value),
}));

watch(
    () => form.username,
    (val) => {
        clearTimeout(usernameDebounce);
        if (usernameAbort) {
            usernameAbort.abort();
        }

        const u = (val || '').trim().toLowerCase();
        if (!u) {
            usernameHint.state = 'idle';
            usernameHint.message = '';
            return;
        }

        if (u.length < 4 || u.length > 20 || !/^[a-zA-Z0-9_]+$/.test(u)) {
            usernameHint.state = 'invalid';
            usernameHint.message = 'Use 4–20 letters, numbers, or underscores only.';
            return;
        }

        usernameHint.state = 'loading';
        usernameHint.message = '';

        usernameDebounce = setTimeout(async () => {
            usernameAbort = new AbortController();
            try {
                const res = await fetch(
                    `/register/username-available?username=${encodeURIComponent(u)}`,
                    { signal: usernameAbort.signal, headers: { Accept: 'application/json' } }
                );
                const data = await res.json();
                if (!data.valid) {
                    usernameHint.state = 'invalid';
                    usernameHint.message = data.message || 'Invalid username.';
                    return;
                }
                usernameHint.state = data.available ? 'available' : 'taken';
                usernameHint.message = data.message || (data.available ? 'Available.' : 'Taken.');
            } catch (e) {
                if (e?.name === 'AbortError') {
                    return;
                }
                usernameHint.state = 'idle';
                usernameHint.message = '';
            }
        }, 400);
    }
);

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>