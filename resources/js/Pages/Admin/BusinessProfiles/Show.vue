<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.business-profiles.index')" class="p-2 text-ink-soft hover:text-ink bg-white border border-line rounded-control shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-ink">Application Review</h1>
                        <p class="text-sm text-ink-soft mt-1">Reviewing B2B account application for {{ profile.company_name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold  "
                          :class="{
                              'bg-amber-100 text-amber-800': profile.status === 'pending',
                              'bg-brand-tint text-brand-dark': profile.status === 'approved',
                              'bg-red-100 text-red-800': profile.status === 'rejected'
                          }">
                        {{ profile.status }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Details -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                        <div class="px-6 py-4 border-b border-line bg-mist">
                            <h2 class="text-lg font-bold text-ink">Business Details</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-xs font-bold text-ink-soft   mb-1">Company Name</p>
                                    <p class="text-sm font-medium text-ink">{{ profile.company_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-ink-soft   mb-1">Business Type</p>
                                    <p class="text-sm font-medium text-ink capitalize">{{ profile.business_type }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-ink-soft   mb-1">TIN Number</p>
                                    <p class="text-sm font-medium text-ink">{{ profile.tin_number || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-ink-soft   mb-1">Date Applied</p>
                                    <p class="text-sm font-medium text-ink">{{ new Date(profile.created_at).toLocaleString() }}</p>
                                </div>
                            </div>

                            <div v-if="profile.sec_dti_document_path" class="border-t border-line pt-6">
                                <p class="text-xs font-bold text-ink-soft   mb-3">Supporting Document (SEC/DTI)</p>
                                <a :href="'/storage/' + profile.sec_dti_document_path" target="_blank"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-mist border border-line rounded-control hover:bg-mist text-sm font-medium text-brand transition">
                                    <svg class="w-5 h-5 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    View Document
                                </a>
                            </div>
                            <div v-else class="border-t border-line pt-6">
                                <p class="text-xs font-bold text-ink-soft   mb-1">Supporting Document</p>
                                <p class="text-sm text-ink-soft italic">No document uploaded.</p>
                            </div>
                            
                            <div v-if="profile.status === 'rejected' && profile.rejection_reason" class="border-t border-line pt-6">
                                <p class="text-xs font-bold text-red-500   mb-2">Rejection Reason</p>
                                <p class="text-sm text-red-700 bg-red-50 p-4 rounded-control border border-red-100">{{ profile.rejection_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar / User Info & Actions -->
                <div class="space-y-6">
                    <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                        <div class="px-6 py-4 border-b border-line bg-mist">
                            <h2 class="text-lg font-bold text-ink">Applicant Info</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-xs font-bold text-ink-soft   mb-1">Name</p>
                                <p class="text-sm font-medium text-ink">{{ profile.user.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-ink-soft   mb-1">Email</p>
                                <p class="text-sm font-medium text-ink">{{ profile.user.email }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-ink-soft   mb-1">Phone</p>
                                <p class="text-sm font-medium text-ink">{{ profile.user.phone_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="profile.status === 'pending'" class="bg-white rounded-card shadow-sm border border-line overflow-hidden p-6 space-y-4">
                        <h2 class="text-sm font-bold text-ink  ">Review Actions</h2>
                        
                        <form @submit.prevent="approve" class="w-full">
                            <button type="submit" :disabled="processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-card shadow-sm text-sm font-bold text-white bg-brand hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand disabled:opacity-50">
                                Approve Application
                            </button>
                        </form>
                        
                        <button @click="showRejectModal = true" type="button" :disabled="processing" class="w-full flex justify-center py-2.5 px-4 border border-red-200 rounded-card shadow-sm text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                            Reject Application
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div v-if="showRejectModal" class="fixed inset-0 z-[100] bg-black/60 flex items-center justify-center p-4">
                <div class="bg-white rounded-card shadow-2xl w-full max-w-lg p-6">
                    <h2 class="text-lg font-bold text-ink mb-4">Reject Application</h2>
                    <p class="text-sm text-ink-soft mb-4">Please provide a reason for rejection. This will be visible to the user.</p>
                    
                    <form @submit.prevent="reject">
                        <textarea v-model="rejectForm.rejection_reason" rows="4" required class="w-full border-2 border-line rounded-card px-4 py-3 text-sm focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 mb-2" placeholder="Reason for rejection..."></textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="text-red-500 text-xs mb-4">{{ rejectForm.errors.rejection_reason }}</p>
                        
                        <div class="flex justify-end gap-3 mt-4">
                            <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-sm font-medium text-ink hover:bg-mist border border-line rounded-control">Cancel</button>
                            <button type="submit" :disabled="rejectForm.processing" class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-control shadow-sm disabled:opacity-50">
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
