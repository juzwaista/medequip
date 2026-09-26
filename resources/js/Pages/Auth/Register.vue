<template>
    <Head title="Create account · MedEquip" />
    <TermsModal :show="showTermsModal" :role="form.role" @close="showTermsModal = false" />

    <AuthLayout
        wide
        headline="Set up your account in three short steps."
        blurb="Buy medical supplies for yourself or your business, or start selling as a distributor."
    >
        <!-- Progress -->
        <ol class="mb-6 flex items-center gap-3 text-sm" aria-label="Registration progress">
            <li
                v-for="(label, i) in ['Who you are', 'Your account', 'Location']"
                :key="label"
                class="flex items-center gap-2"
                :class="i < 2 ? 'flex-1' : ''"
                :aria-current="currentStep === i + 1 ? 'step' : undefined"
            >
                <span
                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                    :class="currentStep > i + 1 ? 'bg-brand text-white' : currentStep === i + 1 ? 'bg-ink text-white' : 'bg-mist text-ink-faint'"
                >{{ i + 1 }}</span>
                <span class="whitespace-nowrap" :class="currentStep === i + 1 ? 'font-medium text-ink' : 'text-ink-soft max-sm:hidden'">{{ label }}</span>
                <span v-if="i < 2" class="h-px flex-1 bg-line max-sm:hidden"></span>
            </li>
        </ol>

        <div class="mb-6 flex items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Create your account</h1>
                <p class="mt-1 text-ink-soft">Step {{ currentStep }} of 3</p>
            </div>
            <p v-if="lastSaved" class="flex items-center gap-1.5 whitespace-nowrap pt-1.5 text-sm text-ink-soft">
                <span class="h-1.5 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>
                Draft saved {{ lastSaved }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">

            <!-- Step 1: who you are -->
            <div v-show="currentStep === 1" class="space-y-6">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-ink">I want to</legend>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <ChoiceCard v-model="form.role" value="customer" name="role" title="Buy supplies" description="Order from verified distributors" />
                        <ChoiceCard v-model="form.role" value="distributor" name="role" title="Sell as a distributor" description="List products and manage inventory" />
                    </div>
                </fieldset>

                <fieldset v-if="form.role === 'customer'">
                    <legend class="mb-2 text-sm font-medium text-ink">Buying as</legend>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <ChoiceCard v-model="form.is_business" :value="false" name="buyer-type" title="Personal" description="Buying for myself" />
                        <ChoiceCard v-model="form.is_business" :value="true" name="buyer-type" title="Business" description="Clinic, hospital, pharmacy" />
                    </div>
                </fieldset>

                <div v-if="needsBusinessDetails" class="space-y-4 rounded-card border border-line bg-mist/60 p-4">
                    <p class="text-sm font-semibold text-ink">Business details</p>
                    <TextInput
                        v-model="form.company_name"
                        label="Company name"
                        type="text"
                        placeholder="Your company name"
                        :error="step1Errors.company_name || form.errors.company_name"
                    />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectInput
                            v-if="form.role === 'customer'"
                            v-model="form.business_type"
                            label="Business type"
                            :error="step1Errors.business_type || form.errors.business_type"
                        >
                            <option value="">Select type</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Clinic">Clinic</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Other">Other</option>
                        </SelectInput>
                        <TextInput
                            v-model="form.tin_number"
                            label="TIN number"
                            optional
                            type="text"
                            placeholder="123-456-789-000"
                            :class="form.role === 'distributor' ? 'sm:col-span-2' : ''"
                        />
                    </div>
                </div>
            </div>

            <!-- Step 2: account credentials -->
            <div v-show="currentStep === 2" class="space-y-4">
                <div>
                    <TextInput
                        v-model="form.username"
                        label="Username"
                        type="text"
                        autocomplete="username"
                        placeholder="e.g. cavite_clinic"
                        hint="4–20 characters: letters, numbers, underscore (stored lowercase)."
                        :error="step2Errors.username || form.errors.username || (usernameHint.state === 'taken' || usernameHint.state === 'invalid' ? usernameHint.message : '')"
                    />
                    <p v-if="usernameHint.state === 'loading'" class="mt-1 flex items-center gap-1.5 text-sm text-ink-soft">
                        <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Checking availability…
                    </p>
                    <p v-else-if="usernameHint.state === 'available'" class="mt-1 text-sm font-medium text-brand">✓ {{ usernameHint.message }}</p>
                </div>

                <TextInput
                    v-model="form.email"
                    label="Email address"
                    type="email"
                    autocomplete="email"
                    placeholder="you@example.com"
                    :error="step2Errors.email || form.errors.email"
                />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <TextInput
                            v-model="form.password"
                            label="Password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            input-class="pr-11"
                            :error="step2Errors.password || form.errors.password"
                        >
                            <template #end>
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="rounded-control p-1.5 text-ink-faint hover:text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand"
                                    :aria-label="showPassword ? 'Hide passwords' : 'Show passwords'"
                                >
                                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </template>
                        </TextInput>
                        <ul v-if="form.password.length > 0" class="mt-2 space-y-0.5 text-sm text-ink-soft" aria-label="Password requirements">
                            <li :class="pwdRules.len ? 'text-brand' : ''">{{ pwdRules.len ? '✓' : '○' }} 10 or more characters</li>
                            <li :class="pwdRules.upper ? 'text-brand' : ''">{{ pwdRules.upper ? '✓' : '○' }} An uppercase letter</li>
                            <li :class="pwdRules.lower ? 'text-brand' : ''">{{ pwdRules.lower ? '✓' : '○' }} A lowercase letter</li>
                            <li :class="pwdRules.num ? 'text-brand' : ''">{{ pwdRules.num ? '✓' : '○' }} A number</li>
                            <li :class="pwdRules.sym ? 'text-brand' : ''">{{ pwdRules.sym ? '✓' : '○' }} A symbol (for example !@#$)</li>
                        </ul>
                    </div>
                    <TextInput
                        v-model="form.password_confirmation"
                        label="Confirm password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        :error="step2Errors.password_confirmation"
                    />
                </div>

                <TextInput
                    v-model="form.contact_number"
                    @input="sanitizeContactNumber"
                    @blur="touchedContact = true"
                    label="Contact number"
                    type="tel"
                    inputmode="numeric"
                    pattern="09[0-9]{9}"
                    maxlength="11"
                    placeholder="09XXXXXXXXX"
                    :error="contactError || form.errors.contact_number"
                />
            </div>

            <!-- Step 3: location and terms -->
            <div v-show="currentStep === 3" class="space-y-4">
                <p class="text-sm font-semibold text-ink">{{ form.role === 'distributor' ? 'Business address' : 'Delivery address' }}</p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <SelectInput
                        v-model="selectedCity"
                        @change="onCityChange"
                        label="City or municipality"
                        :error="step3Errors.city || form.errors.city"
                    >
                        <option value="">Select city</option>
                        <option v-for="(data, city) in cities" :key="city" :value="city">{{ city }}</option>
                    </SelectInput>

                    <SelectInput
                        v-if="availableBarangays.length > 0"
                        v-model="selectedBarangay"
                        label="Barangay"
                        :error="step3Errors.barangay || form.errors.barangay"
                    >
                        <option value="">Select barangay</option>
                        <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">{{ brgy }}</option>
                        <option value="other">Other (type manually)</option>
                    </SelectInput>
                    <TextInput
                        v-else
                        v-model="manualBarangay"
                        label="Barangay"
                        type="text"
                        placeholder="Enter barangay name"
                        :error="step3Errors.barangay || form.errors.barangay"
                    />
                </div>

                <TextInput
                    v-if="selectedBarangay === 'other' && availableBarangays.length > 0"
                    v-model="manualBarangay"
                    label="Barangay name"
                    type="text"
                    placeholder="Type your barangay name"
                />

                <TextInput
                    v-model="form.address_line"
                    label="Street address"
                    type="text"
                    placeholder="e.g. Blk 5 Lot 10 Sampaguita St."
                    :error="step3Errors.address_line || form.errors.address_line"
                />

                <TextInput
                    :model-value="zipCode"
                    label="Zip code"
                    type="text"
                    readonly
                    disabled
                    placeholder="Filled in from your city"
                />

                <!-- Map pin -->
                <div>
                    <p class="mb-1 text-sm font-medium text-ink">
                        Pin your exact location
                        <span class="font-normal text-ink-soft">{{ form.role === 'distributor' ? '(your shop or warehouse)' : '(required for delivery)' }}</span>
                    </p>
                    <MapPicker
                        v-model:lat="form.latitude"
                        v-model:lng="form.longitude"
                        :geocodeQuery="geocodeQuery"
                        height="200px"
                        @update:address="onMapAddressPicked"
                    />
                    <p v-if="detectedLocation" class="mt-2 flex items-center gap-1.5 rounded-control border border-[#B9C7DA] bg-[#F1F5FA] px-3 py-2 text-sm text-seal">
                        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        {{ detectedLocation }}
                    </p>
                    <p v-if="step3Errors.location" class="mt-1 text-sm text-danger">{{ step3Errors.location }}</p>
                    <p v-if="form.errors.latitude" class="mt-1 text-sm text-danger">{{ form.errors.latitude }}</p>
                </div>

                <!-- Terms -->
                <div class="rounded-card border border-line bg-mist/60 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" v-model="form.terms_accepted" class="mt-0.5 h-4 w-4 flex-shrink-0 rounded-control border-line accent-brand">
                        <span class="leading-snug text-ink-soft">
                            I agree to the
                            <button type="button" @click.prevent="showTermsModal = true" class="font-medium text-brand underline underline-offset-2 hover:text-brand-dark">Terms and Conditions</button>
                            of MedEquip, including the
                            <span v-if="form.role === 'customer'" class="font-medium text-ink">Customer Terms</span>
                            <span v-else class="font-medium text-ink">Distributor Terms</span>.
                        </span>
                    </label>
                    <p v-if="step3Errors.terms" class="ml-7 mt-1 text-sm text-danger">{{ step3Errors.terms }}</p>
                    <p v-if="form.errors.terms_accepted" class="ml-7 mt-1 text-sm text-danger">{{ form.errors.terms_accepted }}</p>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between gap-3 pt-2">
                <BaseButton v-if="currentStep > 1" variant="ghost" @click="currentStep--">Back</BaseButton>
                <div v-else></div>

                <!-- Steps 1 and 2. Stays clickable when incomplete so the step's validation messages can appear. -->
                <BaseButton v-if="currentStep < 3" :variant="canAdvance ? 'primary' : 'secondary'" class="min-w-32" @click="tryAdvance">Next</BaseButton>

                <BaseButton v-if="currentStep === 3" type="submit" :disabled="form.processing" class="min-w-40">
                    {{ form.processing ? 'Creating account…' : 'Create account' }}
                </BaseButton>
            </div>
        </form>

        <p class="mt-6 text-center text-ink-soft">
            Already have an account?
            <Link href="/login" class="ml-1 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Sign in</Link>
        </p>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import SelectInput from '@/Components/ui/SelectInput.vue';
import ChoiceCard from '@/Components/ui/ChoiceCard.vue';
import { computed, reactive, ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TermsModal from '@/Components/TermsModal.vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    cities: Object,
    barangays: Object,
});

// ─── UI state ────────────────────────────────────────────────────────────────
const showPassword = ref(false);
const showTermsModal = ref(false);
const currentStep = ref(1);
const touchedContact = ref(false);

// ─── Address refs ─────────────────────────────────────────────────────────────
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

// ─── Map refs ─────────────────────────────────────────────────────────────────
const geocodeQuery = ref(null);
const detectedLocation = ref('');
let detectedTimer = null;
let isProgrammaticChange = false;

// ─── Username availability ────────────────────────────────────────────────────
const usernameHint = reactive({ state: 'idle', message: '' });
let usernameDebounce = null;
let usernameAbort = null;

// ─── Form ─────────────────────────────────────────────────────────────────────
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
    is_business: false,
    company_name: '',
    business_type: '',
    tin_number: '',
});

// ─── Draft persistence ────────────────────────────────────────────────────────
const STORAGE_KEY = 'registration_draft';
const lastSaved = ref(null);

onMounted(() => {
    const draft = localStorage.getItem(STORAGE_KEY);
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            Object.keys(parsed).forEach(key => {
                if (['password', 'password_confirmation'].includes(key)) return;
                if (parsed[key] !== undefined) form[key] = parsed[key];
            });
            if (form.city) {
                selectedCity.value = form.city;
                _applyCityChange(form.city);
                if (props.barangays?.[form.city]) {
                    const list = props.barangays[form.city];
                    if (list.includes(form.barangay)) {
                        selectedBarangay.value = form.barangay;
                    } else if (form.barangay) {
                        selectedBarangay.value = 'other';
                        manualBarangay.value = form.barangay;
                    }
                }
            }
        } catch (e) {
            console.error('Failed to load registration draft', e);
        }
    }
});

watch(() => ({
    username: form.username, email: form.email, role: form.role, contact_number: form.contact_number,
    address_line: form.address_line, city: form.city, barangay: form.barangay,
    latitude: form.latitude, longitude: form.longitude, is_business: form.is_business,
    company_name: form.company_name, business_type: form.business_type, tin_number: form.tin_number,
}), (newVal) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
    lastSaved.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}, { deep: true });

// ─── Computed helpers ─────────────────────────────────────────────────────────
const needsBusinessDetails = computed(() =>
    (form.role === 'customer' && form.is_business) || form.role === 'distributor'
);

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

const pwd = computed(() => form.password || '');
const pwdRules = computed(() => ({
    len: pwd.value.length >= 10,
    upper: /[A-Z]/.test(pwd.value),
    lower: /[a-z]/.test(pwd.value),
    num: /[0-9]/.test(pwd.value),
    sym: /[^A-Za-z0-9]/.test(pwd.value),
}));
const allPwdRulesPass = computed(() => Object.values(pwdRules.value).every(Boolean));

const contactError = computed(() => {
    if (!touchedContact.value && !form.contact_number) return null;
    if (!form.contact_number) return 'Contact number is required.';
    if (!/^09[0-9]{9}$/.test(form.contact_number)) return 'Must be 11 digits starting with 09 (e.g. 09123456789).';
    return null;
});

// ─── Per-step validation ───────────────────────────────────────────────────────
const step1Errors = reactive({ company_name: '', business_type: '' });
const step2Errors = reactive({ username: '', email: '', password: '', password_confirmation: '' });
const step3Errors = reactive({ city: '', barangay: '', address_line: '', location: '', terms: '' });

const validateStep1 = () => {
    step1Errors.company_name = '';
    step1Errors.business_type = '';
    let valid = true;
    if (needsBusinessDetails.value) {
        if (!form.company_name.trim()) {
            step1Errors.company_name = 'Company name is required.';
            valid = false;
        }
        if (form.role === 'customer' && form.is_business && !form.business_type) {
            step1Errors.business_type = 'Business type is required.';
            valid = false;
        }
    }
    return valid;
};

const validateStep2 = () => {
    step2Errors.username = '';
    step2Errors.email = '';
    step2Errors.password = '';
    step2Errors.password_confirmation = '';
    let valid = true;
    if (!form.username.trim()) {
        step2Errors.username = 'Username is required.';
        valid = false;
    } else if (usernameHint.state === 'taken') {
        step2Errors.username = 'This username is already taken.';
        valid = false;
    } else if (usernameHint.state === 'invalid') {
        step2Errors.username = usernameHint.message;
        valid = false;
    } else if (usernameHint.state === 'loading') {
        step2Errors.username = 'Please wait while we check username availability.';
        valid = false;
    }
    if (!form.email.trim()) {
        step2Errors.email = 'Email address is required.';
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        step2Errors.email = 'Enter a valid email address.';
        valid = false;
    }
    if (!allPwdRulesPass.value) {
        step2Errors.password = 'Password does not meet all requirements.';
        valid = false;
    }
    if (form.password !== form.password_confirmation) {
        step2Errors.password_confirmation = 'Passwords do not match.';
        valid = false;
    }
    if (!form.contact_number || !/^09[0-9]{9}$/.test(form.contact_number)) {
        touchedContact.value = true;
        valid = false;
    }
    return valid;
};

const validateStep3 = () => {
    step3Errors.city = '';
    step3Errors.barangay = '';
    step3Errors.address_line = '';
    step3Errors.location = '';
    step3Errors.terms = '';
    let valid = true;
    if (!form.city) { step3Errors.city = 'City is required.'; valid = false; }
    if (!form.barangay) { step3Errors.barangay = 'Barangay is required.'; valid = false; }
    if (!form.address_line.trim()) { step3Errors.address_line = 'Street address is required.'; valid = false; }
    if (!form.latitude || !form.longitude) { step3Errors.location = 'Please pin your location on the map.'; valid = false; }
    if (!form.terms_accepted) { step3Errors.terms = 'You must accept the Terms and Conditions.'; valid = false; }
    return valid;
};

const canAdvance = computed(() => {
    if (currentStep.value === 1) {
        if (needsBusinessDetails.value) {
            if (!form.company_name.trim()) return false;
            if (form.role === 'customer' && form.is_business && !form.business_type) return false;
        }
        return true;
    }
    if (currentStep.value === 2) {
        return form.username.trim() &&
            usernameHint.state === 'available' &&
            form.email.trim() &&
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) &&
            allPwdRulesPass.value &&
            form.password === form.password_confirmation &&
            /^09[0-9]{9}$/.test(form.contact_number);
    }
    return false;
});

const tryAdvance = () => {
    let valid = false;
    if (currentStep.value === 1) valid = validateStep1();
    if (currentStep.value === 2) valid = validateStep2();
    if (valid) currentStep.value++;
};

// ─── Contact number sanitizer ─────────────────────────────────────────────────
const sanitizeContactNumber = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length > 11) val = val.slice(0, 11);
    form.contact_number = val;
};

// ─── Fuzzy matching ───────────────────────────────────────────────────────────
const normalize = (str) =>
    String(str).toLowerCase().normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();

const fuzzyMatch = (input, list) => {
    if (!input || !list?.length) return null;
    const n = normalize(input);
    if (!n) return null;
    return list.find(i => normalize(i) === n)
        || list.find(i => normalize(i).startsWith(n + ' ') || normalize(i) === n)
        || list.find(i => n.includes(normalize(i)))
        || list.find(i => normalize(i).includes(n))
        || null;
};

// ─── City/barangay helpers ────────────────────────────────────────────────────
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    form.city = city || '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
        if ((props.barangays?.[city] || []).length === 0) selectedBarangay.value = 'other';
    } else {
        zipCode.value = '';
    }
};

const onCityChange = () => {
    _applyCityChange(selectedCity.value);
    if (!isProgrammaticChange && selectedCity.value) geocodeQuery.value = `${selectedCity.value}, Cavite, Philippines`;
};

watch(selectedBarangay, (brgy) => {
    if (!isProgrammaticChange && brgy && brgy !== 'other' && selectedCity.value)
        geocodeQuery.value = `Barangay ${brgy}, ${selectedCity.value}, Cavite, Philippines`;
});

watch([selectedCity, selectedBarangay, manualBarangay, zipCode], () => {
    form.city = selectedCity.value || '';
    form.barangay = selectedBarangay.value === 'other' ? (manualBarangay.value || '') : (selectedBarangay.value || '');
});

// ─── Map pin handler ──────────────────────────────────────────────────────────
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
    const brgyLabel = matchedBrgy || (barangay ? `Brgy. ${barangay}` : '');
    if (cityLabel) {
        detectedLocation.value = `Pin Location: ${brgyLabel ? brgyLabel + ', ' : ''}${cityLabel}, Cavite`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }
    isProgrammaticChange = false;
};

// ─── Username watcher ─────────────────────────────────────────────────────────
watch(() => form.username, (val) => {
    clearTimeout(usernameDebounce);
    usernameAbort?.abort();
    const u = (val || '').trim().toLowerCase();
    if (!u) { usernameHint.state = 'idle'; usernameHint.message = ''; return; }
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
            const res = await fetch(`/register/username-available?username=${encodeURIComponent(u)}`, { signal: usernameAbort.signal, headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.valid) { usernameHint.state = 'invalid'; usernameHint.message = data.message || 'Invalid username.'; return; }
            usernameHint.state = data.available ? 'available' : 'taken';
            usernameHint.message = data.message || (data.available ? 'Available!' : 'Username taken.');
        } catch (e) {
            if (e?.name !== 'AbortError') { usernameHint.state = 'idle'; usernameHint.message = ''; }
        }
    }, 400);
});

// ─── Submit ───────────────────────────────────────────────────────────────────
const submit = () => {
    if (!validateStep3()) return;
    form.post('/register', {
        onSuccess: () => localStorage.removeItem(STORAGE_KEY),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>