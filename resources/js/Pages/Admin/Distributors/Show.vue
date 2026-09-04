<template>
    <Head :title="`Review Application - ${distributor.company_name}`" />
    <AdminLayout title="Review Distributor Application">
        <template #actions>
            <Link :href="route('admin.dashboard', { tab: 'shops', shop_status: 'pending' })" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Pending
            </Link>
        </template>

        <div class="max-w-7xl mx-auto py-6 grid lg:grid-cols-3 gap-6">
            
            <!-- Left Side: Documents Viewer -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Submitted Documents
                    </h2>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div v-for="doc in documents" :key="doc.key" class="border border-gray-200 rounded-lg overflow-hidden group">
                            <div class="bg-gray-50 px-3 py-2 border-b border-gray-200 flex justify-between items-center">
                                <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">{{ doc.label }}</span>
                                <a :href="doc.href" target="_blank" class="text-blue-600 hover:text-blue-800" title="Open in new tab">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                            <div class="bg-gray-100 aspect-[4/3] flex items-center justify-center relative overflow-hidden">
                                <img v-if="isImage(doc.href)" :src="doc.href" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                                <div v-else class="text-center p-4">
                                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span class="text-sm font-medium text-gray-600">Document File</span><br/>
                                    <a :href="doc.href" target="_blank" class="text-xs text-blue-600 hover:underline">Click to view</a>
                                </div>
                            </div>
                        </div>
                        <div v-if="!documents.length" class="sm:col-span-2 p-8 text-center text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-xl">
                            No documents uploaded.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Details & Decision Panel -->
            <div class="space-y-6">
                
                <!-- Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-4">
                        <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-yellow-100 text-yellow-800">
                            {{ distributor.status }}
                        </span>
                        <h2 class="text-xl font-bold text-gray-900 mt-2">{{ distributor.company_name }}</h2>
                        <p class="text-sm text-gray-500">Applied on {{ new Date(distributor.created_at).toLocaleDateString() }}</p>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-gray-100">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Owner</p>
                            <p class="text-sm text-gray-900 font-medium">{{ distributor.owner?.name || '—' }}</p>
                            <p class="text-xs text-gray-600">{{ distributor.owner?.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Contact</p>
                            <p class="text-sm text-gray-900">{{ distributor.contact_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Address</p>
                            <p class="text-sm text-gray-900">{{ distributor.address || '—' }}</p>
                        </div>
                        <div v-if="distributor.rejection_count > 0" class="mt-4 p-3 bg-red-50 rounded-lg border border-red-100">
                            <p class="text-xs font-bold text-red-800">PREVIOUS REJECTIONS</p>
                            <p class="text-sm text-red-600 font-medium">{{ distributor.rejection_count }}</p>
                        </div>
                    </div>
                </div>

                <!-- Decision Panel -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Application Decision</h3>
                    
                    <div v-if="distributor.status !== 'pending'" class="text-sm text-gray-500 text-center py-4">
                        This application is no longer pending.
                    </div>
                    
                    <div v-else class="space-y-4">
                        <!-- Approve -->
                        <button @click="approve" :disabled="isProcessing" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl transition shadow-sm disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Approve Application
                        </button>

                        <div class="relative py-2">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                            <div class="relative flex justify-center"><span class="bg-white px-2 text-xs text-gray-400 font-semibold uppercase">Or Reject</span></div>
                        </div>

                        <!-- Reject Form -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <p class="text-xs font-bold text-gray-700 uppercase mb-3">Issues Found:</p>
                            
                            <div class="space-y-2 mb-4">
                                <label v-for="doc in allDocTypes" :key="doc.key" class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" :value="doc.label" v-model="rejectForm.rejected_documents" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500" />
                                    <span class="text-sm text-gray-700">{{ doc.label }}</span>
                                </label>
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" value="Other" v-model="rejectForm.rejected_documents" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500" />
                                    <span class="text-sm text-gray-700">Other</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Additional Notes (Optional)</label>
                                <textarea v-model="rejectForm.reason" rows="3" class="w-full text-sm border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Please clarify the issues..."></textarea>
                            </div>

                            <button @click="reject" :disabled="isProcessing || (!rejectForm.rejected_documents.length && !rejectForm.reason)" class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-lg transition disabled:opacity-50">
                                Reject & Send Feedback
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    distributor: Object,
});

const isProcessing = ref(false);

const allDocTypes = [
    { key: 'dti_sec_path', label: 'DTI/SEC Registration' },
    { key: 'business_license_path', label: 'Business Permit' },
    { key: 'bir_form_path', label: 'BIR Form 2303' },
    { key: 'fda_license_path', label: 'FDA License to Operate' },
    { key: 'prc_id_path', label: 'PRC ID (Pharmacist)' },
    { key: 'valid_id_path', label: 'Government ID' },
    { key: 'authorization_letter_path', label: 'Authorization Letter' },
];

const documents = computed(() => {
    return allDocTypes
        .filter(doc => props.distributor[doc.key])
        .map(doc => ({
            key: doc.key,
            label: doc.label,
            href: `/admin/documents/${props.distributor[doc.key]}`
        }));
});

const isImage = (url) => {
    return url.match(/\.(jpeg|jpg|gif|png)$/i) != null;
};

const rejectForm = ref({
    rejected_documents: [],
    reason: '',
});

const approve = () => {
    if (!confirm('Are you sure you want to approve this distributor?')) return;
    isProcessing.value = true;
    router.post(`/admin/distributors/${props.distributor.id}/approve`, {}, {
        onFinish: () => isProcessing.value = false,
    });
};

const reject = () => {
    if (!confirm('Reject this application and send feedback?')) return;
    isProcessing.value = true;
    router.post(`/admin/distributors/${props.distributor.id}/reject`, rejectForm.value, {
        onFinish: () => isProcessing.value = false,
    });
};
</script>
