<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
            <!-- Header & Navigation -->
            <div class="flex items-center gap-4 mb-6">
                <Link 
                    :href="route('admin.business-profiles.index', { status: profile.status })"
                    class="p-2 rounded-full hover:bg-gray-100 text-gray-500 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Review Application</h1>
                    <p class="text-sm text-gray-500 mt-1">Reviewing B2B Account for: <span class="font-bold text-gray-900">{{ profile.company_name }}</span></p>
                </div>
                <div class="ml-auto">
                    <span 
                        class="px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-widest"
                        :class="{
                            'bg-amber-100 text-amber-800': profile.status === 'pending',
                            'bg-emerald-100 text-emerald-800': profile.status === 'approved',
                            'bg-red-100 text-red-800': profile.status === 'rejected',
                        }"
                    >
                        {{ profile.status }}
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest">Business Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Company Name</label>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ profile.company_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Business Type</label>
                        <p class="mt-1 text-base font-semibold text-gray-900 capitalize">{{ profile.business_type }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Tax ID (TIN)</label>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ profile.tin_number }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Applicant (User)</label>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ profile.user?.name }} ({{ profile.user?.email }})</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest">Registration Document</h2>
                </div>
                <div class="p-6">
                    <a 
                        v-if="profile.sec_dti_document_path"
                        :href="`/storage/${profile.sec_dti_document_path}`" 
                        target="_blank"
                        class="inline-flex items-center gap-2 px-6 py-4 rounded-xl border-2 border-indigo-100 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-100 transition"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        View SEC/DTI Document
                        <svg class="w-4 h-4 ml-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <!-- Actions Panel (Only for Pending) -->
            <div v-if="profile.status === 'pending'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-8">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest">Admin Decision</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="reject" class="mb-8 p-6 bg-red-50 rounded-xl border border-red-100">
                        <h3 class="text-lg font-bold text-red-900 mb-2">Reject Application</h3>
                        <p class="text-sm text-red-700 mb-4">If the documents are invalid, please provide a clear reason.</p>
                        
                        <textarea
                            v-model="rejectForm.reason"
                            rows="3"
                            class="w-full rounded-lg border-red-200 focus:ring-red-500 focus:border-red-500 text-sm mb-3"
                            placeholder="Reason for rejection (mandatory)..."
                            required
                        ></textarea>
                        
                        <button
                            type="submit"
                            :disabled="rejectForm.processing || !rejectForm.reason"
                            class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 disabled:opacity-50 transition"
                        >
                            {{ rejectForm.processing ? 'Rejecting...' : 'Reject Application' }}
                        </button>
                    </form>

                    <div class="p-6 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-emerald-900">Approve Application</h3>
                            <p class="text-sm text-emerald-700 mt-1">This will grant the user B2B features, such as RFQ quoting and wholesale pricing access.</p>
                        </div>
                        <button
                            @click="approve"
                            :disabled="approveForm.processing"
                            class="px-6 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-200 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ approveForm.processing ? 'Approving...' : 'Approve Application' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    profile: Object,
});

const approveForm = useForm({});
const rejectForm = useForm({
    reason: '',
});

const approve = () => {
    if (confirm('Are you sure you want to approve this B2B Account?')) {
        approveForm.post(route('admin.business-profiles.approve', props.profile.id));
    }
};

const reject = () => {
    if (confirm('Are you sure you want to reject this application?')) {
        rejectForm.post(route('admin.business-profiles.reject', props.profile.id));
    }
};
</script>
