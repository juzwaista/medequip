<template>
    <Head :title="`User - ${account.name}`" />
    <AdminLayout title="User Details">
        <template #actions>
            <Link :href="route('admin.users.index', { tab: 'users' })" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Users
            </Link>
        </template>

        <div class="max-w-7xl mx-auto py-6 grid lg:grid-cols-3 gap-6">

            <!-- Left: account activity -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Identity -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold shrink-0">
                            {{ account.name?.charAt(0)?.toUpperCase() || '?' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-xl font-bold text-gray-900">{{ account.name }}</h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600">{{ account.role }}</span>
                                <span v-if="account.banned_at" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-800">Banned</span>
                                <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Active</span>
                            </div>
                            <p v-if="account.username" class="text-sm text-gray-500">@{{ account.username }}</p>
                        </div>
                    </div>

                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 mt-6 pt-6 border-t border-gray-100">
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Email</dt>
                            <dd class="text-sm text-gray-900 break-all">{{ account.email }}</dd>
                            <dd class="text-xs mt-0.5" :class="account.email_verified_at ? 'text-emerald-600' : 'text-amber-600'">
                                {{ account.email_verified_at ? 'Verified' : 'Not verified' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Phone</dt>
                            <dd class="text-sm text-gray-900">{{ account.phone_number || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Joined</dt>
                            <dd class="text-sm text-gray-900">{{ formatDate(account.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 uppercase">Last seen</dt>
                            <dd class="text-sm text-gray-900">{{ account.last_seen_at ? formatDate(account.last_seen_at) : '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Order activity -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Order Activity</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Orders</p>
                            <p class="text-lg font-bold text-gray-900">{{ orderStats.total }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Completed</p>
                            <p class="text-lg font-bold text-emerald-700">{{ orderStats.completed }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Cancelled / rejected</p>
                            <p class="text-lg font-bold text-red-700">{{ orderStats.cancelled }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Total spent</p>
                            <p class="text-lg font-bold text-gray-900">{{ formatMoney(orderStats.spent) }}</p>
                        </div>
                    </div>

                    <div v-if="recentOrders.length" class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="py-2 pr-4">Order</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Total</th>
                                    <th class="py-2">Placed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="o in recentOrders" :key="o.id">
                                    <td class="py-2 pr-4 font-semibold text-gray-900">{{ o.order_number }}</td>
                                    <td class="py-2 pr-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600">{{ o.status.replaceAll('_', ' ') }}</span></td>
                                    <td class="py-2 pr-4 text-gray-700">{{ formatMoney(o.total_amount) }}</td>
                                    <td class="py-2 text-gray-400 text-xs">{{ formatDate(o.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-gray-400">This user hasn't placed any orders.</p>
                </div>

                <!-- Addresses -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Saved Addresses</h3>
                    <ul v-if="addresses.length" class="space-y-3">
                        <li v-for="a in addresses" :key="a.id" class="text-sm">
                            <p class="font-semibold text-gray-900">
                                {{ a.label }}
                                <span v-if="a.is_default" class="ml-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold uppercase">Default</span>
                            </p>
                            <p class="text-gray-600">{{ [a.address_line, a.barangay, a.city, a.province].filter(Boolean).join(', ') }}</p>
                            <p v-if="a.contact_number" class="text-xs text-gray-400">{{ a.contact_number }}</p>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-400">No saved addresses.</p>
                </div>
            </div>

            <!-- Right: status, links, moderation -->
            <div class="space-y-6">

                <!-- Related records -->
                <div v-if="shop || businessProfile || account.company_name" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">Business</h3>

                    <div v-if="shop">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Shop</p>
                        <p class="text-sm font-medium text-gray-900">{{ shop.company_name }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ shop.status }}</p>
                        <Link :href="route('admin.distributors.show', shop.id)" class="inline-block mt-1 text-xs font-bold text-blue-600 hover:text-blue-800">View shop →</Link>
                    </div>

                    <div v-if="businessProfile">
                        <p class="text-xs font-semibold text-gray-500 uppercase">B2B Application</p>
                        <p class="text-sm font-medium text-gray-900">{{ businessProfile.company_name }} <span class="text-gray-400 font-normal">· {{ businessProfile.business_type }}</span></p>
                        <p class="text-xs text-gray-500 capitalize">{{ businessProfile.status }}</p>
                    </div>

                    <div v-if="!shop && !businessProfile && account.company_name">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Company</p>
                        <p class="text-sm font-medium text-gray-900">{{ account.company_name }}</p>
                    </div>
                </div>

                <!-- Moderation -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Moderation</h3>

                    <div v-if="account.banned_at" class="mb-4 p-3 bg-red-50 rounded-lg border border-red-100">
                        <p class="text-xs font-bold text-red-800 uppercase">Banned {{ formatDate(account.banned_at) }}</p>
                        <p class="text-sm text-red-700 mt-1">{{ account.ban_reason || 'No reason recorded.' }}</p>
                    </div>

                    <button v-if="!account.banned_at" type="button" @click="openAction('ban_user')"
                        class="w-full bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-red-700 transition">
                        Ban User
                    </button>
                    <button v-else type="button" @click="openAction('unban_user')"
                        class="w-full bg-emerald-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-emerald-700 transition">
                        Unban User
                    </button>

                    <div v-if="moderationHistory.length" class="mt-5 pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-3">History</p>
                        <ul class="space-y-3">
                            <li v-for="h in moderationHistory" :key="h.id" class="text-sm">
                                <p class="font-semibold text-gray-900">{{ actionLabel(h.action) }}</p>
                                <p v-if="h.reason" class="text-gray-600">{{ h.reason }}</p>
                                <p class="text-xs text-gray-400">{{ h.by ? `by ${h.by} · ` : '' }}{{ formatDate(h.at) }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <ModerationActionModal
            :open="modal.open"
            :action="modal.action"
            :target-id="account.id"
            :target-name="account.name"
            @close="modal.open = false"
        />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ModerationActionModal from '@/Components/Admin/ModerationActionModal.vue';

defineProps({
    account: Object,
    addresses: Array,
    businessProfile: Object,
    shop: Object,
    orderStats: Object,
    recentOrders: Array,
    moderationHistory: Array,
});

const modal = reactive({ open: false, action: '' });
const openAction = (action) => {
    modal.action = action;
    modal.open = true;
};

const ACTION_LABELS = {
    user_banned: 'Banned',
    user_unbanned: 'Unbanned',
    user_role_updated: 'Role changed',
};
const actionLabel = (a) => ACTION_LABELS[a] || a;

const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
const formatMoney = (n) => `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
</script>
