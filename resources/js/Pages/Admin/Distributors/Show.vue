<template>
    <Head :title="`${isPending ? 'Review Application' : 'Distributor'} - ${distributor.company_name}`" />
    <AdminLayout :title="isPending ? 'Review Distributor Application' : 'Distributor Details'">
        <template #actions>
            <Link :href="isPending ? route('admin.dashboard', { tab: 'shops', shop_status: 'pending' }) : route('admin.users.index')" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink bg-white border border-line rounded-control hover:bg-mist transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ isPending ? 'Back to Pending' : 'Back to Users' }}
            </Link>
        </template>

        <div class="max-w-7xl mx-auto py-6 grid lg:grid-cols-3 gap-6">
            
            <!-- Left Side: Documents Viewer -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h2 class="text-lg font-bold text-ink mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Submitted Documents
                    </h2>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div v-for="doc in documents" :key="doc.key" class="border border-line rounded-control overflow-hidden group">
                            <div class="bg-mist px-3 py-2 border-b border-line flex justify-between items-center">
                                <span class="text-xs font-bold text-ink  tracking-wide">{{ doc.label }}</span>
                                <a :href="doc.href" target="_blank" class="text-brand hover:text-brand-dark" title="Open in new tab">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                            <div class="bg-mist aspect-[4/3] flex items-center justify-center relative overflow-hidden">
                                <img v-if="isImage(doc.href)" :src="doc.href" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                                <div v-else class="text-center p-4">
                                    <svg class="w-10 h-10 mx-auto text-ink-faint mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span class="text-sm font-medium text-ink-soft">Document File</span><br/>
                                    <a :href="doc.href" target="_blank" class="text-xs text-brand hover:underline">Click to view</a>
                                </div>
                            </div>
                        </div>
                        <div v-if="!documents.length" class="sm:col-span-2 p-8 text-center text-ink-faint text-sm border-2 border-dashed border-line rounded-card">
                            No documents uploaded.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Details & Decision Panel -->
            <div class="space-y-6">
                
                <!-- Details -->
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <div class="mb-4">
                        <span class="px-2 py-1 rounded-control text-xs font-bold  " :class="statusClasses">
                            {{ distributor.is_suspended ? 'suspended' : distributor.status }}
                        </span>
                        <h2 class="text-xl font-bold text-ink mt-2">{{ distributor.company_name }}</h2>
                        <p class="text-sm text-ink-soft">Applied on {{ new Date(distributor.created_at).toLocaleDateString() }}</p>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-line">
                        <div>
                            <p class="text-xs font-semibold text-ink-soft ">Owner</p>
                            <p class="text-sm text-ink font-medium">{{ distributor.owner?.name || '—' }}</p>
                            <p class="text-xs text-ink-soft">{{ distributor.owner?.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-ink-soft ">Contact</p>
                            <p class="text-sm text-ink">{{ distributor.contact_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-ink-soft ">Address</p>
                            <p class="text-sm text-ink">{{ distributor.address || '—' }}</p>
                        </div>
                        <div v-if="distributor.rejection_count > 0" class="mt-4 p-3 bg-red-50 rounded-control border border-red-100">
                            <p class="text-xs font-bold text-red-800">PREVIOUS REJECTIONS</p>
                            <p class="text-sm text-red-600 font-medium">{{ distributor.rejection_count }}</p>
                        </div>
                    </div>
                </div>

                <!-- Moderation: look through the details above first, then act -->
                <div v-if="canModerate && distributor.status !== 'pending' && distributor.status !== 'rejected'" class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h3 class="text-lg font-bold text-ink mb-4">Moderation</h3>

                    <p v-if="distributor.status === 'banned'" class="text-sm text-ink-soft">This shop is permanently banned.</p>

                    <template v-else-if="distributor.is_suspended">
                        <p class="text-sm text-orange-700 bg-orange-50 border border-orange-100 rounded-control p-3 mb-4">
                            Suspended until {{ new Date(distributor.suspended_until).toLocaleString() }}.
                        </p>
                        <button type="button" @click="openAction('lift')" class="w-full bg-brand text-white px-4 py-2.5 rounded-control text-sm font-bold hover:bg-brand-dark transition">Lift Suspension</button>
                    </template>

                    <div v-else class="space-y-2">
                        <button type="button" @click="openAction('warn')" class="w-full bg-brand text-white px-4 py-2.5 rounded-control text-sm font-bold hover:bg-brand-dark transition">Warn</button>
                        <button type="button" @click="openAction('suspend')" class="w-full bg-orange-600 text-white px-4 py-2.5 rounded-control text-sm font-bold hover:bg-orange-700 transition">Suspend</button>
                        <button type="button" @click="openAction('ban')" class="w-full bg-rose-700 text-white px-4 py-2.5 rounded-control text-sm font-bold hover:bg-rose-800 transition">Ban</button>
                    </div>
                </div>

                <!-- Decision Panel -->
                <div v-if="isPending" class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h3 class="text-lg font-bold text-ink mb-4">Application Decision</h3>

                    <div class="space-y-4">
                        <!-- Approve -->
                        <button @click="approve" :disabled="isProcessing" class="w-full flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white font-bold py-3 px-4 rounded-card transition shadow-sm disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Approve Application
                        </button>

                        <div class="relative py-2">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-line"></div></div>
                            <div class="relative flex justify-center"><span class="bg-white px-2 text-xs text-ink-faint font-semibold ">Or Reject</span></div>
                        </div>

                        <!-- Reject Form -->
                        <div class="bg-mist p-4 rounded-card border border-line">
                            <p class="text-xs font-bold text-ink  mb-3">Issues Found:</p>
                            
                            <div class="space-y-2 mb-4">
                                <label v-for="doc in allDocTypes" :key="doc.key" class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" :value="doc.label" v-model="rejectForm.rejected_documents" class="mt-0.5 rounded border-line text-red-600 focus:ring-red-500" />
                                    <span class="text-sm text-ink">{{ doc.label }}</span>
                                </label>
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" value="Other" v-model="rejectForm.rejected_documents" class="mt-0.5 rounded border-line text-red-600 focus:ring-red-500" />
                                    <span class="text-sm text-ink">Other</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-bold text-ink  mb-1">Additional Notes <span class="text-red-500">*</span></label>
                                <textarea v-model="rejectForm.reason" rows="3" required class="w-full text-sm border-line rounded-control focus:ring-red-500 focus:border-red-500" placeholder="Please clarify the issues..."></textarea>
                            </div>

                            <button @click="reject" :disabled="isProcessing || !rejectForm.reason" class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-control transition disabled:opacity-50">
                                Reject & Send Feedback
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <ModerationActionModal
            :open="modal.open"
            :action="modal.action"
            :target-id="distributor.id"
            :target-name="distributor.company_name"
            @close="modal.open = false"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ModerationActionModal from '@/Components/Admin/ModerationActionModal.vue';

const props = defineProps({
    distributor: Object,
    canModerate: { type: Boolean, default: false },
});

const isProcessing = ref(false);

const isPending = computed(() => props.distributor.status === 'pending');

const statusClasses = computed(() => {
    if (props.distributor.is_suspended) return 'bg-orange-100 text-orange-800';
    return {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-brand-tint text-brand-dark',
        rejected: 'bg-red-100 text-red-800',
        banned: 'bg-ink text-white',
    }[props.distributor.status] || 'bg-mist text-ink';
});

const modal = reactive({ open: false, action: '' });
const openAction = (action) => {
    modal.action = action;
    modal.open = true;
};

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
