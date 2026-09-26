<template>
    <Head title="Apply for B2B Account · MedEquip" />
    <MainLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <!-- Header -->
            <div class="mb-8">
                <Link href="/products" class="inline-flex items-center gap-1.5 text-sm text-ink-soft hover:text-brand transition mb-4">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Products
                </Link>
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-11 w-11 bg-brand-tint rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-ink">Apply for B2B Account</h1>
                        <p class="text-sm text-ink-soft">Unlock wholesale pricing, purchase orders, and request-for-quotation.</p>
                    </div>
                </div>
            </div>

            <!-- Benefits -->
            <div class="bg-gradient-to-br from-brand to-brand border border-brand-soft rounded-card p-5 mb-8">
                <h3 class="text-sm font-bold text-brand-dark mb-3">What you get with a B2B account:</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="benefit in benefits" :key="benefit.title" class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-brand-soft/60 flex items-center justify-center">
                            <svg class="w-3 h-3 text-brand-dark" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ benefit.title }}</p>
                            <p class="text-xs text-ink-soft">{{ benefit.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-card shadow-sm border border-line p-6 sm:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Company / Organization Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.company_name" required placeholder="e.g., Metro Cavite General Hospital"
                            class="w-full border-2 border-line rounded-card px-4 py-3 text-sm focus:outline-none focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all placeholder-ink-faint">
                        <p v-if="form.errors.company_name" class="text-red-600 text-xs mt-1.5">{{ form.errors.company_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Organization Type <span class="text-red-500">*</span></label>
                        <select v-model="form.business_type" required
                            class="w-full border-2 border-line rounded-card px-4 py-3 text-sm focus:outline-none focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all bg-white">
                            <option value="" disabled>Select a type...</option>
                            <option value="Hospital">Hospital / Medical Center</option>
                            <option value="Clinic">Clinic</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Distributor">Wholesaler / Distributor</option>
                            <option value="Government">Government / LGU</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Other">Other Enterprise</option>
                        </select>
                        <p v-if="form.errors.business_type" class="text-red-600 text-xs mt-1.5">{{ form.errors.business_type }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">TIN Number <span class="text-ink-faint font-normal">(Optional)</span></label>
                            <input type="text" v-model="form.tin_number" placeholder="123-456-789-000"
                                class="w-full border-2 border-line rounded-card px-4 py-3 text-sm focus:outline-none focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all placeholder-ink-faint">
                            <p v-if="form.errors.tin_number" class="text-red-600 text-xs mt-1.5">{{ form.errors.tin_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">SEC / DTI Document <span class="text-ink-faint font-normal">(Optional)</span></label>
                            <input type="file" @change="e => form.sec_dti_document = e.target.files[0]" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full text-sm text-ink-soft file:mr-4 file:py-2 file:px-4 file:rounded-card file:border-0 file:text-sm file:font-semibold file:bg-brand-tint file:text-brand-dark hover:file:bg-brand-tint">
                            <p v-if="form.errors.sec_dti_document" class="text-red-600 text-xs mt-1.5">{{ form.errors.sec_dti_document }}</p>
                        </div>
                    </div>

                    <!-- Info note -->
                    <div class="bg-brand-tint border border-brand-soft rounded-control p-4 flex gap-3">
                        <svg class="h-5 w-5 text-brand flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-brand-dark">
                            <strong>No documents required to apply.</strong> You can provide your TIN and business registration later when submitting a formal Purchase Order.
                        </p>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-card shadow-sm text-sm font-bold text-white bg-brand hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand disabled:opacity-50 transition-colors">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                    </button>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const benefits = [
    { title: 'Wholesale Pricing', desc: 'Access bulk pricing from all distributors' },
    { title: 'Request for Quotation', desc: 'Negotiate custom pricing for large orders' },
    { title: 'Purchase Orders', desc: 'Pay via PO with formal documentation' },
    { title: 'Tax Documentation', desc: 'Automatic VAT-compliant invoicing' },
];

const form = useForm({
    company_name: '',
    business_type: '',
    tin_number: '',
    sec_dti_document: null,
});

const submit = () => {
    form.post(route('business-account.store'), {
        forceFormData: true,
    });
};
</script>
