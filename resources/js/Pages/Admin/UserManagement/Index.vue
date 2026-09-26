<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-ink">Users & Shops</h1>
                    <p class="text-ink-soft mt-1">Administrators, distributors, and registered users.</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full rounded-control border border-line py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-brand focus:border-transparent"
                        @keydown.enter="applySearch"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-line mb-6">
                <nav class="-mb-px flex gap-4 sm:gap-6 overflow-x-auto">
                    <button
                        v-for="tab in tabs" :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap',
                            activeTab === tab.key
                                ? 'border-brand text-brand'
                                : 'border-transparent text-ink-soft hover:text-ink hover:border-line'
                        ]"
                    >
                        {{ tab.label }}
                        <span :class="[
                            'ml-1.5 px-1.5 py-0.5 text-xs rounded-full font-bold',
                            activeTab === tab.key ? 'bg-brand-tint text-brand-dark' : 'bg-mist text-ink-soft'
                        ]">{{ tab.count }}</span>
                    </button>
                </nav>
            </div>

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
                                ? 'bg-brand text-white border-brand'
                                : 'bg-white text-ink-soft border-line hover:border-brand-soft'
                        ]"
                    >
                        {{ sf.label }}
                        <span class="ml-1 opacity-70">{{ sf.count }}</span>
                    </Link>
                </div>

                <div v-if="distributors.length" class="space-y-3">
                    <div v-for="d in distributors" :key="d.id" class="bg-white rounded-card shadow-sm border border-line p-5">
                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-ink">{{ d.company_name }}</h3>
                                    <span :class="shopStatusClasses(d.status)" class="px-2 py-0.5 rounded-full text-xs font-bold ">{{ d.status }}</span>
                                    <span v-if="d.rejection_count > 0" class="px-2 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-bold border border-red-100" title="Number of times this application was rejected">
                                        Attempts: {{ d.rejection_count }}
                                    </span>
                                    <span v-if="d.is_suspended" class="px-2 py-0.5 rounded-full bg-ink text-white text-xs font-bold ">Suspended until {{ d.suspended_until }}</span>
                                </div>
                                <p class="text-sm text-ink-soft mt-1">{{ d.owner_name }} &middot; {{ d.owner_email }}</p>
                                <p v-if="d.contact_number || d.address" class="text-xs text-ink-faint mt-0.5">{{ [d.contact_number, d.address].filter(Boolean).join(' · ') }}</p>


                            </div>

                            <!-- Actions: look at the shop first; warn / suspend / ban live on its detail page -->
                            <div class="flex flex-wrap gap-2 lg:flex-col lg:w-36 shrink-0">
                                <Link :href="route('admin.distributors.show', d.id)" class="flex-1 lg:w-full bg-brand text-white px-3 py-2 rounded-control text-xs font-bold hover:bg-brand-dark transition text-center">
                                    {{ d.status === 'pending' ? 'Review Application' : 'View Details' }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white rounded-card border border-line p-12 text-center text-ink-faint text-sm">
                    No shops match your filters.
                </div>
            </template>

            <!-- PLATFORM USERS TAB -->
            <template v-if="activeTab === 'users'">
                <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-mist text-xs text-ink-soft  ">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Joined</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-for="u in platformUsers" :key="u.id" class="hover:bg-mist/50">
                                <td class="px-6 py-3 font-semibold text-ink">{{ u.name }}</td>
                                <td class="px-6 py-3 text-ink-soft">{{ u.email }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold  bg-mist text-ink-soft">{{ u.role }}</span>
                                </td>
                                <td class="px-6 py-3 text-ink-faint text-xs">{{ formatDate(u.created_at) }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="u.banned_at" class="px-2 py-0.5 rounded-full text-xs font-bold  bg-red-100 text-red-800">Banned</span>
                                    <span v-else class="px-2 py-0.5 rounded-full text-xs font-bold  bg-brand-tint text-brand-dark">Active</span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <Link :href="route('admin.users.show', u.id)" class="text-xs text-brand font-semibold hover:text-brand-dark">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="!platformUsers.length" class="p-12 text-center text-ink-faint text-sm">No users match your search.</div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    distributors: Array,
    platformUsers: Array,
    isSuperAdmin: Boolean,
    filters: Object,
    shopCounts: Object,
});

const searchInput = ref(props.filters?.search || '');
const activeTab = ref(new URLSearchParams(window.location.search).get('tab') === 'users' ? 'users' : 'shops');

const tabs = computed(() => [
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

const shopStatusClasses = (status) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-brand-tint text-brand-dark': status === 'approved',
    'bg-red-100 text-red-800': status === 'rejected',
    'bg-ink text-white': status === 'banned',
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
