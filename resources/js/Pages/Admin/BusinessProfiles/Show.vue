<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.business-profiles.index')" class="p-2 text-gray-500 hover:text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Application Review</h1>
                        <p class="text-sm text-gray-500 mt-1">Reviewing B2B account application for {{ profile.company_name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                          :class="{
                              'bg-amber-100 text-amber-800': profile.status === 'pending',
                              'bg-emerald-100 text-emerald-800': profile.status === 'approved',
                              'bg-red-100 text-red-800': profile.status === 'rejected'
                          }">
                        {{ profile.status }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Details -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-bold text-gray-900">Business Details</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Company Name</p>
                                    <p class="text-sm font-medium text-gray-900">{{ profile.company_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Business Type</p>
                                    <p class="text-sm font-medium text-gray-900 capitalize">{{ profile.business_type }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">TIN Number</p>
                                    <p class="text-sm font-medium text-gray-900">{{ profile.tin_number || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Date Applied</p>
                                    <p class="text-sm font-medium text-gray-900">{{ new Date(profile.created_at).toLocaleString() }}</p>
                                </div>
                            </div>

                            <div v-if="profile.sec_dti_document_path" class="border-t border-gray-100 pt-6">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Supporting Document (SEC/DTI)</p>
                                <a :href="'/storage/' + profile.sec_dti_document_path" target="_blank"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 text-sm font-medium text-blue-600 transition">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    View Document
                                </a>
                            </div>
                            <div v-else class="border-t border-gray-100 pt-6">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Supporting Document</p>
                                <p class="text-sm text-gray-500 italic">No document uploaded.</p>
                            </div>
                            
                            <div v-if="profile.status === 'rejected' && profile.rejection_reason" class="border-t border-gray-100 pt-6">
                                <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-2">Rejection Reason</p>
                                <p class="text-sm text-red-700 bg-red-50 p-4 rounded-lg border border-red-100">{{ profile.rejection_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar / User Info & Actions -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-bold text-gray-900">Applicant Info</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Name</p>
                                <p class="text-sm font-medium text-gray-900">{{ profile.user.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email</p>
                                <p class="text-sm font-medium text-gray-900">{{ profile.user.email }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Phone</p>
                                <p class="text-sm font-medium text-gray-900">{{ profile.user.phone_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="profile.status === 'pending'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden p-6 space-y-4">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Review Actions</h2>
                        
                        <form @submit.prevent="approve" class="w-full">
                            <button type="submit" :disabled="processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50">
                                Approve Application
                            </button>
                        </form>
                        
                        <button @click="showRejectModal = true" type="button" :disabled="processing" class="w-full flex justify-center py-2.5 px-4 border border-red-200 rounded-xl shadow-sm text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                            Reject Application
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div v-if="showRejectModal" class="fixed inset-0 z-[100] bg-black/60 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Reject Application</h2>
                    <p class="text-sm text-gray-500 mb-4">Please provide a reason for rejection. This will be visible to the user.</p>
                    
                    <form @submit.prevent="reject">
                        <textarea v-model="rejectForm.rejection_reason" rows="4" required class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 mb-2" placeholder="Reason for rejection..."></textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="text-red-500 text-xs mb-4">{{ rejectForm.errors.rejection_reason }}</p>
                        
                        <div class="flex justify-end gap-3 mt-4">
                            <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-lg">Cancel</button>
                            <button type="submit" :disabled="rejectForm.processing" class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm disabled:opacity-50">
                                Confirm Rejection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    profile: Object,
});

const showRejectModal = ref(false);
const processing = ref(false);

const approveForm = useForm({});
const approve = () => {
    processing.value = true;
    approveForm.post(route('admin.business-profiles.approve', props.profile.id), {
        onFinish: () => {
            processing.value = false;
        }
    });
};

const rejectForm = useForm({
    rejection_reason: '',
});
const reject = () => {
    rejectForm.post(route('admin.business-profiles.reject', props.profile.id), {
        onSuccess: () => {
            showRejectModal.value = false;
        }
    });
};
</script>
