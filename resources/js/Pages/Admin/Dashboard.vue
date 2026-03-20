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
                <div v-if="pendingDistributors.length > 0" class="divide-y">
                    <div v-for="distributor in pendingDistributors" :key="distributor.id" class="p-6 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-900">{{ distributor.company_name }}</h3>
                                <p class="text-sm text-gray-600 mt-1">Owner: {{ distributor.owner?.name ?? '—' }} ({{ distributor.owner?.email ?? '' }})</p>
                                <p class="text-sm text-gray-500 mt-1">Registered: {{ formatDate(distributor.created_at) }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-4 max-w-2xl">
                                    <div>
                                        <p class="text-xs text-gray-500">Contact</p>
                                        <p class="text-sm font-medium">{{ distributor.contact_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Address</p>
                                        <p class="text-sm font-medium">{{ distributor.address }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-3 ml-4">
                                <button 
                                    @click="approveDistributor(distributor.id)"
                                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition font-semibold"
                                >
                                    ✓ Approve
                                </button>
                                <button 
                                    @click="rejectDistributor(distributor.id)"
                                    class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition font-semibold"
                                >
                                    ✗ Reject
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
