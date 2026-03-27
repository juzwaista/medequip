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
    </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    pendingDistributors: Array,
    recentActivity: Array,
});

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
