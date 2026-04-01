<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="text-gray-600 mt-2">Platform oversight and distributor verification</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <p class="text-sm text-gray-600 mb-1">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900">{{ stats.totalUsers }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <p class="text-sm text-gray-600 mb-1">Active Distributors</p>
                    <p class="text-3xl font-bold text-green-600">{{ stats.totalDistributors }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <p class="text-sm text-gray-600 mb-1">Pending Verification</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ stats.pendingDistributors }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <p class="text-sm text-gray-600 mb-1">Total Products</p>
                    <p class="text-3xl font-bold text-blue-600">{{ stats.totalProducts }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <p class="text-sm text-gray-600 mb-1">Total Orders</p>
                    <p class="text-3xl font-bold text-purple-600">{{ stats.totalOrders }}</p>
                </div>
            </div>

            <!-- DSS Risk Assessment -->
            <div class="bg-white rounded-xl shadow-md mb-8 border-l-4 border-rose-500 overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-rose-50/30 flex items-center gap-3">
                    <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">At-Risk Distributors</h2>
                        <p class="text-sm text-gray-600">Automated system risk assessment</p>
                    </div>
                </div>
                <div v-if="atRiskDistributors && atRiskDistributors.length > 0" class="divide-y relative">
                    <div v-for="distributor in atRiskDistributors" :key="'risk-' + distributor.id" class="p-6 transition border-b" :class="distributor.is_suspended ? 'bg-gray-50 opacity-75' : 'hover:bg-rose-50/20'">
                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                            <div class="flex-1 w-full">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="font-bold text-lg text-gray-900">{{ distributor.company_name }}</h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide" 
                                          :class="{'bg-rose-100 text-rose-800': distributor.risk_level === 'Critical', 'bg-orange-100 text-orange-800': distributor.risk_level === 'High', 'bg-yellow-100 text-yellow-800': distributor.risk_level === 'Medium'}">
                                        {{ distributor.risk_level }} RISK
                                    </span>
                                    <span v-if="distributor.is_suspended" class="px-2.5 py-0.5 rounded-full bg-slate-800 text-white text-xs font-bold uppercase tracking-wide">
                                        Suspended until {{ distributor.suspended_until }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-4">Owner: {{ distributor.owner_email }} | Contact: {{ distributor.contact_number }}</p>
                                
                                <h4 class="text-xs font-bold text-rose-800 uppercase tracking-widest mb-2">Detected Risk Factors:</h4>
                                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 mb-4">
                                    <li v-for="reason in distributor.reasons" :key="reason">{{ reason }}</li>
                                </ul>
                            </div>
                            <div class="flex flex-col gap-2 w-full md:w-auto mt-4 md:mt-0 pt-4 md:pt-0 border-t md:border-t-0 border-gray-100">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest text-center md:text-right mb-1">Recommended Action</p>
                                <button v-if="!distributor.is_suspended" @click="openWarningModal(distributor.id)" class="w-full md:w-48 bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition shadow-sm font-bold text-sm mb-2">
                                    Issue a Warning
                                </button>
                                <button v-if="!distributor.is_suspended" @click="openSuspendModal(distributor.id)" class="w-full md:w-48 bg-orange-600 text-white px-4 py-2.5 rounded-lg hover:bg-orange-700 transition shadow-sm font-bold text-sm mb-2">
                                    Suspend Account
                                </button>
                                <button v-if="!distributor.is_suspended" @click="banDistributor(distributor.id)" class="w-full md:w-48 bg-rose-700 text-white px-4 py-2.5 rounded-lg hover:bg-rose-800 transition shadow-sm font-bold text-sm">
                                    Permanent Ban
                                </button>
                                <button v-if="distributor.is_suspended" @click="liftSuspension(distributor.id)" class="w-full md:w-48 bg-emerald-600 text-white px-4 py-2.5 rounded-lg hover:bg-emerald-700 transition shadow-sm font-bold text-sm mb-2 drop-shadow-sm">
                                    Lift Suspension
                                </button>
                                <div v-if="distributor.is_suspended" class="w-full md:w-48 bg-gray-100 text-gray-400 px-4 py-2.5 rounded-lg text-center font-bold text-xs border border-gray-200 uppercase tracking-tighter">
                                    Currently Restricted
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center text-gray-500 bg-white">
                    <svg class="h-16 w-16 text-emerald-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium text-emerald-800">All clear!</p>
                    <p class="text-sm text-gray-500 mt-1">No distributors are currently flagged by the DSS Risk Assessment.</p>
                </div>
            </div>

            <!-- Pending Distributor Verifications -->
            <div class="bg-white rounded-xl shadow-md mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Pending Distributor Verifications</h2>
                </div>
                <div v-if="pendingDistributors.length > 0" class="divide-y relative">
                    <div v-for="distributor in pendingDistributors" :key="distributor.id" class="p-6 hover:bg-gray-50 transition border-b">
                        <div class="flex flex-col sm:flex-row justify-between items-start">
                            <div class="flex-1 w-full relative">
                                <h3 class="font-bold text-lg text-gray-900">{{ distributor.company_name }}</h3>
                                <p class="text-sm text-gray-600 mt-1">Owner: {{ distributor.owner?.name ?? '—' }} ({{ distributor.owner?.email ?? '' }})</p>
                                <p class="text-sm text-gray-500 mt-1">Registered: {{ formatDate(distributor.created_at) }}</p>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Contact</p>
                                        <p class="text-sm font-bold text-gray-900">{{ distributor.contact_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Address</p>
                                        <p class="text-sm font-bold text-gray-900">{{ distributor.address }}</p>
                                    </div>
                                </div>
                                <div class="mt-6 border-t border-gray-100 pt-5">
                                    <h4 class="text-xs font-bold text-gray-400 mb-3 uppercase tracking-wider">Submitted Verification Documents</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        <a v-if="distributor.dti_sec_path" :href="`/admin/documents/${distributor.dti_sec_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition font-medium border border-blue-100">
                                            📄 DTI/SEC Reg
                                        </a>
                                        <a v-if="distributor.business_license_path" :href="`/admin/documents/${distributor.business_license_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition font-medium border border-blue-100">
                                            📄 Business Permit
                                        </a>
                                        <a v-if="distributor.bir_form_path" :href="`/admin/documents/${distributor.bir_form_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition font-medium border border-blue-100">
                                            📄 BIR Form
                                        </a>
                                        <a v-if="distributor.fda_license_path" :href="`/admin/documents/${distributor.fda_license_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition font-medium border border-blue-100">
                                            📄 FDA License (LTO)
                                        </a>
                                        <a v-if="distributor.prc_id_path" :href="`/admin/documents/${distributor.prc_id_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition font-medium border border-blue-100">
                                            📄 Pharmacist PRC ID
                                        </a>
                                        <a v-if="distributor.valid_id_path" :href="`/admin/documents/${distributor.valid_id_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition font-medium border border-blue-100">
                                            📄 Valid Gov ID
                                        </a>
                                        <a v-if="distributor.authorization_letter_path" :href="`/admin/documents/${distributor.authorization_letter_path}`" target="_blank" class="inline-flex items-center gap-2 text-sm text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-3 py-2 rounded-lg transition font-medium border border-amber-100">
                                            📄 Auth Letter 
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3 ml-0 sm:ml-6 mt-6 sm:mt-0 w-full sm:w-auto h-full justify-start border-t sm:border-t-0 border-gray-100 pt-6 sm:pt-0">
                                <button 
                                    @click="approveDistributor(distributor.id)"
                                    class="w-full sm:w-32 bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700 transition shadow-sm font-bold"
                                >
                                    Approve
                                </button>
                                <button 
                                    @click="rejectDistributor(distributor.id)"
                                    class="w-full sm:w-32 bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition shadow-sm font-bold"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center text-gray-500">
                    <svg class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium">No pending verifications</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Recent Distributor Activity</h2>
                </div>
                <div class="divide-y">
                    <div v-for="activity in recentActivity" :key="activity.id" class="p-4 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-gray-900">{{ activity.company_name }}</p>
                                <p class="text-sm text-gray-600">{{ activity.user_name }}</p>
                            </div>
                            <div class="text-right">
                                <span 
                                    :class="{
                                        'bg-green-100 text-green-800': activity.status === 'approved',
                                        'bg-yellow-100 text-yellow-800': activity.status === 'pending',
                                        'bg-red-100 text-red-800': activity.status === 'rejected',
                                    }"
                                    class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                >
                                    {{ activity.status }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">{{ activity.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Modal -->
        <div v-if="showWarningModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" @click="showWarningModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">
                                Issue a Warning
                            </h3>
                            <div class="mt-1">
                                <p class="text-sm text-gray-500">
                                    Select a reason for the warning, and optionally add a custom message.
                                </p>
                            </div>
                            <div class="mt-4 space-y-4 text-left">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Reason</label>
                                    <select v-model="warningData.preset_reason" class="mt-1 block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg shadow-sm border">
                                        <option value="" disabled>Select a reason...</option>
                                        <option value="High Cancellation Rate">High Cancellation Rate</option>
                                        <option value="Fulfillment Delays">Fulfillment Delays (>48 hours)</option>
                                        <option value="Zero Active Inventory">Zero Active Inventory</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Custom Message Dialog</label>
                                    <textarea v-model="warningData.custom_message" rows="3" class="mt-1 block w-full p-3 shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-lg bg-gray-50" placeholder="Enter custom message here..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 sm:mt-5 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="submitWarning" :disabled="!warningData.preset_reason" class="inline-flex justify-center w-full px-4 py-2.5 text-base font-bold text-white bg-blue-600 border border-transparent rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            Send Warning
                        </button>
                        <button type="button" @click="showWarningModal = false" class="mt-3 inline-flex justify-center w-full px-4 py-2.5 text-base font-bold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Suspend Dialog -->
        <div v-if="showSuspendModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/75 backdrop-blur-sm transition-opacity">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-5 border-b border-gray-100 bg-orange-50/50">
                    <div class="flex items-center gap-3">
                        <div class="bg-orange-100 p-2 rounded-xl text-orange-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900 leading-tight">Suspend Distributor</h3>
                            <p class="text-xs text-orange-700 font-medium">Temporarily restrict account access</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Reason for Suspension <span class="text-rose-500">*</span></label>
                        <select v-model="suspendData.reason" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-orange-500 focus:border-orange-500 block p-2.5" required>
                            <option value="" disabled>Select the primary reason</option>
                            <option value="Sustained High Cancellation Rate">Repeated Order Cancellations</option>
                            <option value="Severe Fulfillment Delays">Failure to Fulfill Orders on Time</option>
                            <option value="Policy Violation">Platform Policy Violation</option>
                            <option value="Customer Complaints">High Volume of Customer Complaints</option>
                            <option value="Regulatory Action (FDA)">Pending FDA/Regulatory Verification</option>
                            <option value="Other/Administrative">Other Administrative Action</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Duration (Days) <span class="text-rose-500">*</span></label>
                        <input type="number" min="1" max="365" v-model="suspendData.days" placeholder="7" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-orange-500 focus:border-orange-500 block p-2.5" required>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-2 rounded-b-2xl">
                    <button type="button" @click="showSuspendModal = false" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 transition mt-2 sm:mt-0">
                        Cancel
                    </button>
                    <button type="button" @click="submitSuspension" :disabled="!suspendData.reason || !suspendData.days" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-white bg-orange-600 border border-transparent rounded-xl shadow-sm hover:bg-orange-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        Finalize Suspension
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    pendingDistributors: Array,
    recentActivity: Array,
    atRiskDistributors: Array,
});

const showSuspendModal = ref(false);
const suspendDistributorId = ref(null);
const suspendData = ref({
    reason: '',
    days: 7
});

const openSuspendModal = (id) => {
    suspendDistributorId.value = id;
    suspendData.value = { reason: '', days: 7 };
    showSuspendModal.value = true;
};

const submitSuspension = () => {
    if (suspendData.value.reason && suspendData.value.days >= 1) {
        router.post(route('admin.distributors.suspend', suspendDistributorId.value), suspendData.value, {
            onSuccess: () => {
                showSuspendModal.value = false;
            }
        });
    }
};

const liftSuspension = (id) => {
    console.log('Attempting to lift suspension for ID:', id);
    if (confirm('Are you sure you want to lift the suspension for this distributor? They will be able to accept new orders immediately.')) {
        router.post(route('admin.distributors.lift-suspension', id), {}, {
            onStart: () => console.log('Lift suspension request started'),
            onSuccess: () => console.log('Lift suspension request successful'),
            onError: (errors) => console.error('Lift suspension request failed:', errors)
        });
    }
};

const banDistributor = (id) => {
    if (confirm('Are you absolutely sure you want to PERMANENTLY BAN this distributor? This action will hide all their products immediately.')) {
        const reason = prompt('Reason for ban (required):');
        if (reason) {
            router.post(route('admin.distributors.ban', id), { reason: reason });
        }
    }
};

const showWarningModal = ref(false);
const warningDistributorId = ref(null);
const warningData = ref({
    preset_reason: '',
    custom_message: ''
});

const openWarningModal = (id) => {
    warningDistributorId.value = id;
    warningData.value = { preset_reason: '', custom_message: '' };
    showWarningModal.value = true;
};

const submitWarning = () => {
    router.post(route('admin.distributors.warn', warningDistributorId.value), warningData.value, {
        onSuccess: () => {
            showWarningModal.value = false;
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};

const approveDistributor = (id) => {
    if (confirm('Are you sure you want to approve this distributor?')) {
        router.post(route('admin.distributors.approve', id));
    }
};

const rejectDistributor = (id) => {
    const reason = prompt('Rejection reason (optional):');
    if (reason !== null) {
        router.post(route('admin.distributors.reject', id), { reason });
    }
};
</script>
