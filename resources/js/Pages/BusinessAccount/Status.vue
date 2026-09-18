<template>
    <Head title="B2B Account Status · MedEquip" />
    <MainLayout>
        <OnboardingWizardModal v-if="autoQualified || profile?.status === 'approved'" type="buyer" />
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <!-- Back Link -->
            <Link href="/products" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition mb-6">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Products
            </Link>

            <!-- Auto-qualified (verified distributor) -->
            <div v-if="autoQualified" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">You have B2B Access</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            As a verified distributor, you automatically have wholesale buying access on all shops. Browse products from other distributors to see wholesale pricing.
                        </p>
                        <Link href="/products" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition">
                            Browse Products
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Application status -->
            <div v-else-if="profile" class="space-y-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="h-11 w-11 rounded-xl flex items-center justify-center"
                        :class="{
                            'bg-amber-100': profile.status === 'pending',
                            'bg-emerald-100': profile.status === 'approved',
                            'bg-red-100': profile.status === 'rejected',
                        }">
                        <!-- Pending icon -->
                        <svg v-if="profile.status === 'pending'" class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <!-- Approved icon -->
                        <svg v-else-if="profile.status === 'approved'" class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <!-- Rejected icon -->
                        <svg v-else class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ profile.company_name }}</h1>
                        <p class="text-sm text-gray-500">B2B Application</p>
                    </div>
                </div>

                <!-- Status Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <!-- Missing Document -->
                    <div v-if="profile.status === 'pending' && !profile.sec_dti_document_path">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Action Required</span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 mb-2">Upload your Business Document</h2>
                        <p class="text-sm text-gray-700 leading-relaxed mb-6">
                            To activate your wholesale pricing access, we need to verify your business. Please upload a clear copy of your SEC, DTI Certificate, or Mayor's Permit.
                        </p>
                        
                        <form @submit.prevent="uploadDoc" class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">SEC / DTI Document <span class="text-red-500">*</span></label>
                            <input type="file" required @change="e => form.sec_dti_document = e.target.files[0]" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-3">
                            <p v-if="form.errors.sec_dti_document" class="text-red-600 text-xs mb-3">{{ form.errors.sec_dti_document }}</p>
                            
                            <button type="submit" :disabled="form.processing"
                                class="w-full sm:w-auto flex justify-center py-2.5 px-6 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50 transition-colors">
                                <svg v-if="form.processing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ form.processing ? 'Uploading...' : 'Upload Document' }}
                            </button>
                        </form>

                        <div class="mt-6 bg-amber-50 border border-amber-100 rounded-lg p-4">
                            <p class="text-sm text-amber-800">
                                <strong>While you wait:</strong> You can continue browsing and purchasing products at retail prices. Wholesale pricing will be unlocked once your document is approved.
                            </p>
                        </div>
                    </div>

                    <!-- Pending (with Document) -->
                    <div v-else-if="profile.status === 'pending' && profile.sec_dti_document_path">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Pending Review</span>
                        </div>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            Your application is being reviewed by our team. This usually takes 1-2 business days. We'll notify you via email once a decision is made.
                        </p>
                        <div class="mt-6 bg-amber-50 border border-amber-100 rounded-lg p-4">
                            <p class="text-sm text-amber-800">
                                <strong>While you wait:</strong> You can continue browsing and purchasing products at retail prices. Wholesale pricing will be unlocked once your application is approved.
                            </p>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div v-else-if="profile.status === 'approved'">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">Approved</span>
                        </div>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            Your B2B account is active! You now have access to wholesale pricing, can submit purchase orders, and request custom quotations from any distributor.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link href="/products" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                                Browse Wholesale Products
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </Link>
                        </div>

                        <!-- Upgrade to Distributor CTA -->
                        <div v-if="!isDistributor" class="mt-8 pt-6 border-t border-gray-100">
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Want to sell on MedEquip too?</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Set up your own shop and start selling to hospitals, clinics, and other businesses.</p>
                                    <Link :href="route('owner.distributors.create', { from: 'b2b' })" class="inline-flex items-center gap-1.5 mt-2 text-sm font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                        Start Selling →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div v-else-if="profile.status === 'rejected'">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 uppercase tracking-wider">Rejected</span>
                        </div>
                        <p class="text-sm text-gray-700 leading-relaxed mb-4">
                            Unfortunately, your B2B application was not approved at this time.
                        </p>
                        <div v-if="profile.rejection_reason" class="bg-red-50 border border-red-100 rounded-lg p-4 mb-6">
                            <p class="text-xs font-bold text-red-900 uppercase tracking-wider mb-1">Reason</p>
                            <p class="text-sm text-red-800">{{ profile.rejection_reason }}</p>
                        </div>
                        <p class="text-sm text-gray-600">
                            If you believe this is an error or your circumstances have changed, please
                            <Link href="/contact" class="text-blue-600 font-semibold hover:underline">contact our support team</Link>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import OnboardingWizardModal from '@/Components/OnboardingWizardModal.vue';

const props = defineProps({
    profile: { type: Object, default: null },
    autoQualified: { type: Boolean, default: false },
});

const page = usePage();
const isDistributor = computed(() => page.props.auth?.user?.role === 'distributor');

const form = useForm({
    sec_dti_document: null,
});

const uploadDoc = () => {
    form.post(route('business-account.upload-document'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
