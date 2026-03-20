<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Financial Controls: Withdrawals</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Withdrawal Requests List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">Payout Queue</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">User & Bank</th>
                                        <th scope="col" class="px-6 py-3">Account Details</th>
                                        <th scope="col" class="px-6 py-3">Amount</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="req in requests" :key="req.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900">{{ req.wallet?.user?.name || 'Unknown User' }}</div>
                                            <div class="text-xs text-gray-500">{{ req.bank_name }}</div>
                                            <div class="text-xs text-gray-400 mt-1">Requested: {{ new Date(req.created_at).toLocaleDateString() }}</div>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-gray-600">
                                            <div>{{ req.account_name }}</div>
                                            <div>{{ req.account_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-gray-900">
                                            ₱{{ formatCurrency(req.amount) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                :class="{
                                                    'bg-yellow-100 text-yellow-800': req.status === 'pending',
                                                    'bg-green-100 text-green-800': req.status === 'completed',
                                                    'bg-red-100 text-red-800': req.status === 'rejected',
                                                }"
                                                class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase"
                                            >
                                                {{ req.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <template v-if="req.status === 'pending'">
                                                <button @click="approve(req.id)" class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded hover:bg-green-700 transition">
                                                    Approve
                                                </button>
                                                <button @click="reject(req.id)" class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700 transition">
                                                    Reject
                                                </button>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">
                                                    Processed {{ new Date(req.processed_at).toLocaleDateString() }}
                                                    <span v-if="req.processor">by Admin {{ req.processor.id }}</span>
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr v-if="requests.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No withdrawal requests found.</td>
                                    </tr>
                                </tbody>
                            </table>
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
    requests: Array,
});

const formatCurrency = (value) => {
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const approve = (id) => {
    if (confirm('Are you sure you want to approve this withdrawal? The funds will be deducted from the user\'s wallet.')) {
        router.post(route('admin.withdrawals.approve', id));
    }
};

const reject = (id) => {
    const reason = prompt('Please provide a reason for rejecting this withdrawal:');
    if (reason !== null && reason.trim() !== '') {
        router.post(route('admin.withdrawals.reject', id), { notes: reason });
    }
};
</script>
