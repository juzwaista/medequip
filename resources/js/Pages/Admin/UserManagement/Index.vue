<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Users & Shops</h1>
                    <p class="text-gray-500 mt-1">Administrators, distributors, and registered users.</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        @keydown.enter="applySearch"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex gap-4 sm:gap-6 overflow-x-auto">
                    <button
                        v-for="tab in tabs" :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap',
                            activeTab === tab.key
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        {{ tab.label }}
                        <span :class="[
                            'ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full font-bold',
                            activeTab === tab.key ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'
                        ]">{{ tab.count }}</span>
                    </button>
                </nav>
            </div>

            <!-- ADMINS TAB -->
            <template v-if="activeTab === 'admins'">
                <div v-if="isSuperAdmin" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Create Admin Account</h2>
                    <p class="text-sm text-gray-500 mb-5">Add a new administrator with limited permissions.</p>
                    <form @submit.prevent="submitAdmin" class="max-w-xl space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" v-model="adminForm.name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
                                <p v-if="adminForm.errors.name" class="text-red-500 text-xs mt-1">{{ adminForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" v-model="adminForm.email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
                                <p v-if="adminForm.errors.email" class="text-red-500 text-xs mt-1">{{ adminForm.errors.email }}</p>
                            </div>
                        </div>
                        <button type="submit" :disabled="adminForm.processing" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50 font-bold uppercase tracking-wider">
                            Send Invitation
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="a in admins" :key="a.id" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3 font-semibold text-gray-900">{{ a.name }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ a.email }}</td>
                                <td class="px-6 py-3">
                                    <span :class="a.role === 'super_admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800'" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ a.role.replace('_', ' ') }}</span>
                                </td>
                                <td class="px-6 py-3 text-gray-400 text-xs">{{ formatDate(a.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- SHOPS TAB -->
            <template v-if="activeTab === 'shops'">
                <!-- Status Filter Tabs -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <Link
                        v-for="sf in shopFilters"
                        :key="sf.key"
                        :href="`/admin/users?shop_status=${sf.key}&search=${encodeURIComponent(filters.search)}`"
                        preserve-state
                        :class="[
                            'px-3 py-1.5 rounded-full text-xs font-bold transition border',
                            filters.shop_status === sf.key
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300'
                        ]"
                    >
                        {{ sf.label }}
                        <span class="ml-1 opacity-70">{{ sf.count }}</span>
                    </Link>
                </div>

                <div v-if="distributors.length" class="space-y-3">
                    <div v-for="d in distributors" :key="d.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-gray-900">{{ d.company_name }}</h3>
                                    <span :class="shopStatusClasses(d.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ d.status }}</span>
                                    <span v-if="d.is_suspended" class="px-2 py-0.5 rounded-full bg-gray-800 text-white text-[10px] font-bold uppercase">Suspended until {{ d.suspended_until }}</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ d.owner_name }} &middot; {{ d.owner_email }}</p>
                                <p v-if="d.contact_number || d.address" class="text-xs text-gray-400 mt-0.5">{{ [d.contact_number, d.address].filter(Boolean).join(' · ') }}</p>

                                <!-- Documents -->
                                <div v-if="hasDocuments(d)" class="mt-3 flex flex-wrap gap-2">
                                    <a v-for="doc in getDocuments(d)" :key="doc.label" :href="doc.href" target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-blue-600 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-lg border border-blue-100 font-medium transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        {{ doc.label }}
                                    </a>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2 lg:flex-col lg:w-36 shrink-0">
                                <template v-if="d.status === 'pending'">
                                    <button @click="openAction('approve', d)" class="flex-1 lg:w-full bg-emerald-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition">Approve</button>
                                    <button @click="openAction('reject', d)" class="flex-1 lg:w-full bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-700 transition">Reject</button>
                                </template>
                                <template v-if="d.status === 'approved' && !d.is_suspended">
                                    <button @click="openAction('warn', d)" class="flex-1 lg:w-full bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 transition">Warn</button>
                                    <button @click="openAction('suspend', d)" class="flex-1 lg:w-full bg-orange-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-orange-700 transition">Suspend</button>
                                    <button @click="openAction('ban', d)" class="flex-1 lg:w-full bg-rose-700 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-rose-800 transition">Ban</button>
                                </template>
                                <template v-if="d.is_suspended">
                                    <button @click="openAction('lift', d)" class="flex-1 lg:w-full bg-emerald-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition">Lift Suspension</button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white rounded-xl border border-gray-100 p-12 text-center text-gray-400 text-sm">
                    No shops match your filters.
                </div>
            </template>

            <!-- PLATFORM USERS TAB -->
            <template v-if="activeTab === 'users'">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Joined</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="u in platformUsers" :key="u.id" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3 font-semibold text-gray-900">{{ u.name }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ u.email }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600">{{ u.role }}</span>
                                </td>
                                <td class="px-6 py-3 text-gray-400 text-xs">{{ formatDate(u.created_at) }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="u.banned_at" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-800">Banned</span>
                                    <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Active</span>
                                </td>
                                <td class="px-6 py-3">
                                    <button v-if="!u.banned_at" @click="openAction('ban_user', u)" class="text-xs text-red-600 font-semibold hover:text-red-800">Ban</button>
                                    <button v-else @click="unbanUser(u.id)" class="text-xs text-emerald-600 font-semibold hover:text-emerald-800">Unban</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="!platformUsers.length" class="p-12 text-center text-gray-400 text-sm">No users match your search.</div>
                </div>
            </template>
        </div>

        <!-- Action Modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/75 backdrop-blur-sm" @click.self="modal.open = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
                    <div class="px-6 py-5 border-b border-gray-100" :class="modalHeaderBg">
                        <h3 class="text-lg font-bold text-gray-900">{{ modalTitle }}</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Approve -->
                        <p v-if="modal.action === 'approve'" class="text-sm text-gray-600">
                            Approve <strong>{{ modal.target?.company_name }}</strong>? They will be able to list products immediately.
                        </p>

                        <!-- Reject -->
                        <template v-if="modal.action === 'reject'">
                            <p class="text-sm text-gray-600">Provide a reason for rejecting <strong>{{ modal.target?.company_name }}</strong> (optional).</p>
                            <textarea v-model="modal.reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Reason..."></textarea>
                        </template>

                        <!-- Warn -->
                        <template v-if="modal.action === 'warn'">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Reason</label>
                                <select v-model="modal.preset_reason" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm">
                                    <option value="" disabled>Select a reason...</option>
                                    <option value="High Cancellation Rate">High Cancellation Rate</option>
                                    <option value="Fulfillment Delays (>48 hours)">Fulfillment Delays (>48 hours)</option>
                                    <option value="Zero Active Inventory">Zero Active Inventory</option>
                                    <option value="Reported via moderation">Reported via moderation</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <textarea v-model="modal.reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Optional message..."></textarea>
                        </template>

                        <!-- Suspend -->
                        <template v-if="modal.action === 'suspend'">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Reason</label>
                                <select v-model="modal.reason" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm">
                                    <option value="" disabled>Select a reason...</option>
                                    <option value="Sustained High Cancellation Rate">Repeated Cancellations</option>
                                    <option value="Severe Fulfillment Delays">Fulfillment Delays</option>
                                    <option value="Policy Violation">Policy Violation</option>
                                    <option value="Customer Complaints">Customer Complaints</option>
                                    <option value="Regulatory Action (FDA)">Regulatory Verification</option>
                                    <option value="Other/Administrative">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Duration (days)</label>
                                <input type="number" min="1" max="365" v-model="modal.days" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm" />
                            </div>
                        </template>

                        <!-- Ban -->
                        <template v-if="modal.action === 'ban'">
                            <p class="text-sm text-gray-600">Permanently ban <strong>{{ modal.target?.company_name }}</strong>? All products will be hidden.</p>
                            <textarea v-model="modal.reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Reason for ban (required)..." required></textarea>
                        </template>

                        <!-- Lift -->
                        <p v-if="modal.action === 'lift'" class="text-sm text-gray-600">
                            Lift suspension for <strong>{{ modal.target?.company_name }}</strong>? They will be able to accept orders immediately.
                        </p>

                        <!-- Ban User -->
                        <template v-if="modal.action === 'ban_user'">
                            <p class="text-sm text-gray-600">Ban user <strong>{{ modal.target?.name }}</strong>?</p>
                            <textarea v-model="modal.reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Reason for ban (required)..." required></textarea>
                        </template>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                        <button @click="modal.open = false" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button @click="submitAction" :disabled="!canSubmit" :class="modalConfirmClass" class="px-4 py-2 text-sm font-bold text-white rounded-lg transition disabled:opacity-50">
                            {{ modalConfirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    admins: Array,
    distributors: Array,
    platformUsers: Array,
    isSuperAdmin: Boolean,
    filters: Object,
    shopCounts: Object,
});

const activeTab = ref('shops');
const searchInput = ref(props.filters?.search || '');

const tabs = computed(() => [
    { key: 'admins', label: 'Admins', count: props.admins?.length || 0 },
    { key: 'shops', label: 'Shops', count: props.shopCounts?.all || 0 },
    { key: 'users', label: 'Users', count: props.platformUsers?.length || 0 },
]);

const shopFilters = computed(() => [
    { key: 'all', label: 'All', count: props.shopCounts?.all || 0 },
    { key: 'pending', label: 'Pending', count: props.shopCounts?.pending || 0 },
    { key: 'approved', label: 'Approved', count: props.shopCounts?.approved || 0 },
    { key: 'rejected', label: 'Rejected', count: props.shopCounts?.rejected || 0 },
    { key: 'banned', label: 'Banned', count: props.shopCounts?.banned || 0 },
]);

const applySearch = () => {
    router.get('/admin/users', {
        search: searchInput.value,
        shop_status: props.filters?.shop_status || 'all',
    }, { preserveState: true, replace: true });
};

const adminForm = useForm({
    name: '', email: '',
});
const submitAdmin = () => {
    adminForm.post('/admin/users', { onSuccess: () => adminForm.reset() });
};

const modal = reactive({
    open: false,
    action: '',
    target: null,
    reason: '',
    preset_reason: '',
    days: 7,
});

const openAction = (action, target) => {
    modal.action = action;
    modal.target = target;
    modal.reason = '';
    modal.preset_reason = '';
    modal.days = 7;
    modal.open = true;
};

const modalTitle = computed(() => ({
    approve: 'Approve Distributor',
    reject: 'Reject Distributor',
    warn: 'Issue Warning',
    suspend: 'Suspend Distributor',
    ban: 'Permanently Ban Distributor',
    lift: 'Lift Suspension',
    ban_user: 'Ban User',
}[modal.action] || ''));

const modalHeaderBg = computed(() => ({
    approve: 'bg-emerald-50',
    reject: 'bg-red-50',
    warn: 'bg-blue-50',
    suspend: 'bg-orange-50',
    ban: 'bg-rose-50',
    lift: 'bg-emerald-50',
    ban_user: 'bg-red-50',
}[modal.action] || ''));

const modalConfirmClass = computed(() => ({
    approve: 'bg-emerald-600 hover:bg-emerald-700',
    reject: 'bg-red-600 hover:bg-red-700',
    warn: 'bg-blue-600 hover:bg-blue-700',
    suspend: 'bg-orange-600 hover:bg-orange-700',
    ban: 'bg-rose-700 hover:bg-rose-800',
    lift: 'bg-emerald-600 hover:bg-emerald-700',
    ban_user: 'bg-red-600 hover:bg-red-700',
}[modal.action] || 'bg-blue-600'));

const modalConfirmLabel = computed(() => ({
    approve: 'Approve',
    reject: 'Reject',
    warn: 'Send Warning',
    suspend: 'Suspend',
    ban: 'Confirm Ban',
    lift: 'Lift Suspension',
    ban_user: 'Confirm Ban',
}[modal.action] || 'Confirm'));

const canSubmit = computed(() => {
    if (modal.action === 'ban' || modal.action === 'ban_user') return modal.reason.trim().length > 0;
    if (modal.action === 'warn') return !!modal.preset_reason;
    if (modal.action === 'suspend') return !!modal.reason && modal.days >= 1;
    return true;
});

const submitAction = () => {
    const id = modal.target?.id;
    const close = () => { modal.open = false; };

    switch (modal.action) {
        case 'approve':
            router.post(`/admin/distributors/${id}/approve`, {}, { onSuccess: close });
            break;
        case 'reject':
            router.post(`/admin/distributors/${id}/reject`, { reason: modal.reason }, { onSuccess: close });
            break;
        case 'warn':
            router.post(`/admin/distributors/${id}/warn`, { preset_reason: modal.preset_reason, custom_message: modal.reason }, { onSuccess: close });
            break;
        case 'suspend':
            router.post(`/admin/distributors/${id}/suspend`, { reason: modal.reason, days: modal.days }, { onSuccess: close });
            break;
        case 'ban':
            router.post(`/admin/distributors/${id}/ban`, { reason: modal.reason }, { onSuccess: close });
            break;
        case 'lift':
            router.post(`/admin/distributors/${id}/lift-suspension`, {}, { onSuccess: close });
            break;
        case 'ban_user':
            router.post(`/admin/users/${id}/ban`, { reason: modal.reason }, { onSuccess: close });
            break;
    }
};

const unbanUser = (id) => router.post(`/admin/users/${id}/unban`);

const shopStatusClasses = (status) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-emerald-100 text-emerald-800': status === 'approved',
    'bg-red-100 text-red-800': status === 'rejected',
    'bg-gray-800 text-white': status === 'banned',
});

const docMap = [
    { key: 'dti_sec_path', label: 'DTI/SEC' },
    { key: 'business_license_path', label: 'Business Permit' },
    { key: 'bir_form_path', label: 'BIR Form' },
    { key: 'fda_license_path', label: 'FDA License' },
    { key: 'prc_id_path', label: 'PRC ID' },
    { key: 'valid_id_path', label: 'Gov ID' },
    { key: 'authorization_letter_path', label: 'Auth Letter' },
];

const hasDocuments = (d) => docMap.some(m => d[m.key]);
const getDocuments = (d) => docMap.filter(m => d[m.key]).map(m => ({ label: m.label, href: `/admin/documents/${d[m.key]}` }));

const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
</script>
