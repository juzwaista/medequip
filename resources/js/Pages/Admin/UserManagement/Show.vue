<template>
    <Head :title="`User - ${account.name}`" />
    <AdminLayout title="User Details">
        <template #actions>
            <Link :href="route('admin.users.index', { tab: 'users' })" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink bg-white border border-line rounded-control hover:bg-mist transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Users
            </Link>
        </template>

        <div class="max-w-7xl mx-auto py-6 grid lg:grid-cols-3 gap-6">

            <!-- Left: account activity -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Identity -->
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-full bg-brand-tint text-brand-dark flex items-center justify-center text-xl font-bold shrink-0">
                            {{ account.name?.charAt(0)?.toUpperCase() || '?' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-xl font-bold text-ink">{{ account.name }}</h2>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold  bg-mist text-ink-soft">{{ account.role }}</span>
                                <span v-if="account.banned_at" class="px-2 py-0.5 rounded-full text-xs font-bold  bg-red-100 text-red-800">Banned</span>
                                <span v-else class="px-2 py-0.5 rounded-full text-xs font-bold  bg-brand-tint text-brand-dark">Active</span>
                            </div>
                            <p v-if="account.username" class="text-sm text-ink-soft">@{{ account.username }}</p>
                        </div>
                    </div>

                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 mt-6 pt-6 border-t border-line">
                        <div>
                            <dt class="text-xs font-semibold text-ink-soft ">Email</dt>
                            <dd class="text-sm text-ink break-all">{{ account.email }}</dd>
                            <dd class="text-xs mt-0.5" :class="account.email_verified_at ? 'text-brand' : 'text-amber-600'">
                                {{ account.email_verified_at ? 'Verified' : 'Not verified' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-ink-soft ">Phone</dt>
                            <dd class="text-sm text-ink">{{ account.phone_number || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-ink-soft ">Joined</dt>
                            <dd class="text-sm text-ink">{{ formatDate(account.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-ink-soft ">Last seen</dt>
                            <dd class="text-sm text-ink">{{ account.last_seen_at ? formatDate(account.last_seen_at) : '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Order activity -->
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h3 class="text-lg font-bold text-ink mb-4">Order Activity</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                        <div class="bg-mist rounded-control p-3">
                            <p class="text-xs text-ink-soft">Orders</p>
                            <p class="text-lg font-bold text-ink">{{ orderStats.total }}</p>
                        </div>
                        <div class="bg-mist rounded-control p-3">
                            <p class="text-xs text-ink-soft">Completed</p>
                            <p class="text-lg font-bold text-brand-dark">{{ orderStats.completed }}</p>
                        </div>
                        <div class="bg-mist rounded-control p-3">
                            <p class="text-xs text-ink-soft">Cancelled / rejected</p>
                            <p class="text-lg font-bold text-red-700">{{ orderStats.cancelled }}</p>
                        </div>
                        <div class="bg-mist rounded-control p-3">
                            <p class="text-xs text-ink-soft">Total spent</p>
                            <p class="text-lg font-bold text-ink">{{ formatMoney(orderStats.spent) }}</p>
                        </div>
                    </div>

                    <div v-if="recentOrders.length" class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs text-ink-soft  ">
                                <tr>
                                    <th class="py-2 pr-4">Order</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Total</th>
                                    <th class="py-2">Placed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="o in recentOrders" :key="o.id">
                                    <td class="py-2 pr-4 font-semibold text-ink">{{ o.order_number }}</td>
                                    <td class="py-2 pr-4"><span class="px-2 py-0.5 rounded-full text-xs font-bold  bg-mist text-ink-soft">{{ o.status.replaceAll('_', ' ') }}</span></td>
                                    <td class="py-2 pr-4 text-ink">{{ formatMoney(o.total_amount) }}</td>
                                    <td class="py-2 text-ink-faint text-xs">{{ formatDate(o.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-ink-faint">This user hasn't placed any orders.</p>
                </div>

                <!-- Addresses -->
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h3 class="text-lg font-bold text-ink mb-4">Saved Addresses</h3>
                    <ul v-if="addresses.length" class="space-y-3">
                        <li v-for="a in addresses" :key="a.id" class="text-sm">
                            <p class="font-semibold text-ink">
                                {{ a.label }}
                                <span v-if="a.is_default" class="ml-1 px-1.5 py-0.5 rounded bg-brand-tint text-brand-dark text-xs font-bold ">Default</span>
                            </p>
                            <p class="text-ink-soft">{{ [a.address_line, a.barangay, a.city, a.province].filter(Boolean).join(', ') }}</p>
                            <p v-if="a.contact_number" class="text-xs text-ink-faint">{{ a.contact_number }}</p>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-ink-faint">No saved addresses.</p>
                </div>
            </div>

            <!-- Right: status, links, moderation -->
            <div class="space-y-6">

                <!-- Related records -->
                <div v-if="shop || businessProfile || account.company_name" class="bg-white rounded-card shadow-sm border border-line p-6 space-y-4">
                    <h3 class="text-lg font-bold text-ink">Business</h3>

                    <div v-if="shop">
                        <p class="text-xs font-semibold text-ink-soft ">Shop</p>
                        <p class="text-sm font-medium text-ink">{{ shop.company_name }}</p>
                        <p class="text-xs text-ink-soft capitalize">{{ shop.status }}</p>
                        <Link :href="route('admin.distributors.show', shop.id)" class="inline-block mt-1 text-xs font-bold text-brand hover:text-brand-dark">View shop →</Link>
                    </div>

                    <div v-if="businessProfile">
                        <p class="text-xs font-semibold text-ink-soft ">B2B Application</p>
                        <p class="text-sm font-medium text-ink">{{ businessProfile.company_name }} <span class="text-ink-faint font-normal">· {{ businessProfile.business_type }}</span></p>
                        <p class="text-xs text-ink-soft capitalize">{{ businessProfile.status }}</p>
                    </div>

                    <div v-if="!shop && !businessProfile && account.company_name">
                        <p class="text-xs font-semibold text-ink-soft ">Company</p>
                        <p class="text-sm font-medium text-ink">{{ account.company_name }}</p>
                    </div>
                </div>

                <!-- Moderation -->
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h3 class="text-lg font-bold text-ink mb-4">Moderation</h3>

                    <div v-if="account.banned_at" class="mb-4 p-3 bg-red-50 rounded-control border border-red-100">
                        <p class="text-xs font-bold text-red-800 ">Banned {{ formatDate(account.banned_at) }}</p>
                        <p class="text-sm text-red-700 mt-1">{{ account.ban_reason || 'No reason recorded.' }}</p>
                    </div>

                    <button v-if="!account.banned_at" type="button" @click="openAction('ban_user')"
                        class="w-full bg-red-600 text-white px-4 py-2.5 rounded-control text-sm font-bold hover:bg-red-700 transition">
                        Ban User
                    </button>
                    <button v-else type="button" @click="openAction('unban_user')"
                        class="w-full bg-brand text-white px-4 py-2.5 rounded-control text-sm font-bold hover:bg-brand-dark transition">
                        Unban User
                    </button>

                    <div v-if="moderationHistory.length" class="mt-5 pt-5 border-t border-line">
                        <p class="text-xs font-semibold text-ink-soft  mb-3">History</p>
                        <ul class="space-y-3">
                            <li v-for="h in moderationHistory" :key="h.id" class="text-sm">
                                <p class="font-semibold text-ink">{{ actionLabel(h.action) }}</p>
                                <p v-if="h.reason" class="text-ink-soft">{{ h.reason }}</p>
                                <p class="text-xs text-ink-faint">{{ h.by ? `by ${h.by} · ` : '' }}{{ formatDate(h.at) }}</p>
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
