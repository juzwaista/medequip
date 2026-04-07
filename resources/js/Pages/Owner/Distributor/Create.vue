<template>
    <OnboardingLayout title="Distributor Application — MedEquip">
        <div class="w-full max-w-2xl">

            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-0 mb-8">
                <template v-for="(s, i) in steps" :key="i">
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 border-2"
                            :class="currentStep > i
                                ? 'bg-blue-600 border-blue-600 text-white'
                                : currentStep === i
                                    ? 'bg-white border-blue-600 text-blue-600'
                                    : 'bg-white border-gray-200 text-gray-400'">
                            <svg v-if="currentStep > i" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <p class="text-[10px] font-semibold mt-1 text-center max-w-[60px] leading-tight"
                            :class="currentStep === i ? 'text-blue-600' : 'text-gray-400'">
                            {{ s.label }}
                        </p>
                    </div>
                    <div v-if="i < steps.length - 1"
                        class="flex-1 h-0.5 mx-1 mb-5 transition-all duration-300"
                        :class="currentStep > i ? 'bg-blue-600' : 'bg-gray-200'">
                    </div>
                </template>
            </div>

            <!-- Flash errors -->
            <div v-if="Object.keys(form.errors).length" class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-red-700 mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key" class="text-sm text-red-600">{{ err }}</li>
                </ul>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">

                <!-- ========= STEP 1: Welcome ========= -->
                <div v-if="currentStep === 0" class="p-8">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">Become a MedEquip Distributor</h1>
                        <p class="text-gray-500 text-sm max-w-md mx-auto leading-relaxed">
                            Join our network of trusted medical equipment distributors. The process takes about 5 minutes and requires a few business documents.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                        <div v-for="item in requirements" :key="item.title" class="bg-gray-50 rounded-xl p-4 text-center border border-gray-100">
                            <div class="w-10 h-10 mx-auto mb-2 flex items-center justify-center bg-blue-50 rounded-xl">
                                <!-- document -->
                                <svg v-if="item.icon === 'document'" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <!-- shield -->
                                <svg v-else-if="item.icon === 'shield'" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <!-- id -->
                                <svg v-else class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </div>
                            <p class="text-xs font-bold text-gray-700">{{ item.title }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ item.desc }}</p>
                        </div>
                    </div>

                    <button @click="currentStep = 1"
                        class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition shadow-md flex items-center justify-center gap-2">
                        Get Started
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>

                <!-- ========= STEP 2: Business Info ========= -->
                <div v-else-if="currentStep === 1" class="p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Business Information</h2>
                    <p class="text-sm text-gray-500 mb-6">Tell us about your company.</p>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Company / Business Name <span class="text-red-500">*</span></label>
                            <input v-model="form.company_name" type="text" placeholder="e.g. Acme Medical Supplies Co."
                                class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                :class="form.errors.company_name ? 'border-red-300 bg-red-50' : 'border-gray-300'"/>
                            <p v-if="form.errors.company_name" class="text-red-500 text-xs mt-1">{{ form.errors.company_name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Business Address <span class="text-red-500">*</span></label>
                            <textarea v-model="form.address" rows="3" placeholder="Unit, Street, Barangay, City, Province"
                                class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                                :class="form.errors.address ? 'border-red-300 bg-red-50' : 'border-gray-300'"></textarea>
                            <p v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                                <input v-model="form.contact_number" @input="sanitizeContactNumber"
                                    type="tel" inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX"
                                    class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    :class="form.errors.contact_number ? 'border-red-300 bg-red-50' : 'border-gray-300'"/>
                                <p v-if="form.errors.contact_number" class="text-red-500 text-xs mt-1">{{ form.errors.contact_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Business Email <span class="text-red-500">*</span></label>
                                <input v-model="form.email" type="email" placeholder="contact@yourbusiness.com"
                                    class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    :class="form.errors.email ? 'border-red-300 bg-red-50' : 'border-gray-300'"/>
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button @click="currentStep = 0" class="flex-none px-5 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition text-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back
                        </button>
                        <button @click="validateStep1" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition text-sm flex items-center justify-center gap-1.5">
                            Continue
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </div>
                </div>

                <!-- ========= STEP 3: Documents ========= -->
                <div v-else-if="currentStep === 2" class="p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Upload Documents</h2>
                    <p class="text-sm text-gray-500 mb-6">PDF, JPG, or PNG — max 10 MB per file.</p>

                    <div class="space-y-4">
                        <template v-for="doc in docFields" :key="doc.key">
                            <div class="rounded-xl border p-4 transition"
                                :class="form[doc.key] ? 'border-blue-200 bg-blue-50/50' : (form.errors[doc.key] ? 'border-red-200 bg-red-50' : 'border-gray-200 hover:border-gray-300')">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <label :for="doc.key" class="block text-sm font-semibold text-gray-800 mb-0.5 cursor-pointer">
                                            {{ doc.label }}
                                            <span v-if="!doc.optional" class="text-red-500"> *</span>
                                            <span v-else class="text-gray-400 font-normal"> (optional)</span>
                                        </label>
                                        <p v-if="doc.hint" class="text-xs text-gray-500">{{ doc.hint }}</p>
                                        <p v-if="form.errors[doc.key]" class="text-xs text-red-600 mt-1">{{ form.errors[doc.key] }}</p>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <span v-if="form[doc.key]" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full truncate max-w-[100px]">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ form[doc.key].name }}
                                        </span>
                                        <label :for="doc.key" class="cursor-pointer bg-white border border-gray-300 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-gray-50 transition flex-shrink-0">
                                            {{ form[doc.key] ? 'Change' : 'Upload' }}
                                        </label>
                                        <input :id="doc.key" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            @change="e => handleFileChange(doc.key, e.target.files[0])"
                                            class="sr-only"/>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button @click="currentStep = 1" class="flex-none px-5 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition text-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back
                        </button>
                        <button @click="validateStep2" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition text-sm flex items-center justify-center gap-1.5">
                            Review &amp; Submit
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </div>
                </div>

                <!-- ========= STEP 4: Review (compact) ========= -->
                <div v-else-if="currentStep === 3" class="p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Ready to submit?</h2>
                    <p class="text-sm text-gray-500 mb-5">Double-check below. Use Back to edit any step.</p>

                    <div class="rounded-xl border border-gray-200 bg-white p-4 mb-5 text-sm space-y-3">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Business</p>
                            <p class="font-semibold text-gray-900 mt-0.5">{{ form.company_name }}</p>
                            <p class="text-gray-600 text-xs mt-1 line-clamp-2">{{ form.address }}</p>
                            <p class="text-gray-600 text-xs mt-1">{{ form.contact_number }} · {{ form.email }}</p>
                        </div>
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Documents</p>
                            <p class="text-gray-700">
                                <span class="text-green-600 font-semibold">{{ uploadedDocCount }}</span> of {{ requiredDocCount }} required files attached
                                <span v-if="form.authorization_letter" class="text-gray-500"> + authorization letter</span>
                            </p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p class="text-xs text-amber-700 leading-relaxed">By submitting, you confirm that all information is accurate and all documents are authentic. False submissions may result in permanent account termination.</p>
                    </div>

                    <div class="flex gap-3">
                        <button @click="currentStep = 2" class="flex-none px-5 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition text-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back
                        </button>
                        <button @click="submit" :disabled="form.processing"
                            class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                        </button>
                    </div>
                </div>

            </div>

            <!-- Support note -->
            <p class="text-center text-xs text-gray-400 mt-5">
                Need help? Email us at <a href="mailto:support@medequip.ph" class="text-blue-500 hover:underline">support@medequip.ph</a>
            </p>
        </div>
    </OnboardingLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';

const currentStep = ref(0);

const steps = [
    { label: 'Welcome' },
    { label: 'Business Info' },
    { label: 'Documents' },
    { label: 'Review' },
];

const requirements = [
    { icon: 'document', title: 'Business Documents', desc: 'DTI/SEC, Business Permit, BIR Form' },
    { icon: 'shield',   title: 'FDA Compliance',     desc: 'License to Operate (LTO) & PRC ID' },
    { icon: 'id',       title: 'Personal ID',         desc: 'Valid government-issued ID' },
];

const docFields = [
    { key: 'dti_sec',              label: 'DTI Certificate or SEC Registration', short: 'DTI/SEC',         hint: null,             optional: false },
    { key: 'business_license',     label: 'Business Permit',                      short: 'Business Permit', hint: null,             optional: false },
    { key: 'bir_form',             label: 'BIR Form (e.g. 2303)',                 short: 'BIR Form',        hint: null,             optional: false },
    { key: 'fda_license',          label: 'FDA License to Operate (LTO)',         short: 'FDA LTO',         hint: null,             optional: false },
    { key: 'prc_id',               label: 'Pharmacist / Qualified Person PRC ID', short: 'PRC ID',          hint: null,             optional: false },
    { key: 'valid_id',             label: 'Primary Valid Government ID',           short: 'Gov\'t ID',       hint: 'Driver\'s License, Passport, UMID, etc.', optional: false },
    { key: 'authorization_letter', label: 'Authorization Letter',                  short: 'Auth Letter',     hint: 'Required only if you are not the business owner.', optional: true },
];

const form = useForm({
    company_name: '',
    address: '',
    contact_number: '',
    email: '',
    valid_id: null,
    business_license: null,
    dti_sec: null,
    bir_form: null,
    fda_license: null,
    prc_id: null,
    authorization_letter: null,
});

const requiredDocCount = computed(() => docFields.filter((d) => !d.optional).length);
const uploadedDocCount = computed(() => docFields.filter((d) => !d.optional && form[d.key]).length);

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || '').replace(/\D/g, '').slice(0, 11);
};

const handleFileChange = (key, file) => {
    if (!file) {
        form[key] = null;
        return;
    }
    
    // 10MB Limit for client-side check
    if (file.size > 10 * 1024 * 1024) {
        form.errors[key] = 'File is too large (max 10MB).';
        form[key] = null;
        return;
    }
    
    delete form.errors[key];
    form[key] = file;
};

const validateStep1 = () => {
    if (!form.company_name.trim()) { form.errors.company_name = 'Company name is required.'; return; }
    if (!form.address.trim())      { form.errors.address = 'Address is required.'; return; }
    if (!/^09[0-9]{9}$/.test(form.contact_number)) { form.errors.contact_number = 'Must be 11 digits starting with 09.'; return; }
    if (!form.email.trim())        { form.errors.email = 'Email is required.'; return; }
    // Clear manual errors
    delete form.errors.company_name;
    delete form.errors.address;
    delete form.errors.contact_number;
    delete form.errors.email;
    currentStep.value = 2;
};

const validateStep2 = () => {
    const required = docFields.filter(d => !d.optional);
    const missing = required.find(d => !form[d.key]);
    if (missing) {
        form.errors[missing.key] = `${missing.label} is required.`;
        return;
    }
    currentStep.value = 3;
};

const submit = () => {
    form.post('/owner/distributor/store', {
        forceFormData: true,
        onError: () => {
            // If there are validation errors from server, jump to the relevant step
            const step1Keys = ['company_name', 'address', 'contact_number', 'email'];
            const hasStep1Err = step1Keys.some(k => form.errors[k]);
            if (hasStep1Err) { currentStep.value = 1; return; }
            const hasDocErr = docFields.some(d => form.errors[d.key]);
            if (hasDocErr) { currentStep.value = 2; }
        },
    });
};
</script>
