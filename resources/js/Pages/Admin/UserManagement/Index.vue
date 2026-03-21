<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                <p class="text-gray-500 mt-1">Manage platform administrators, distributors, and users</p>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex gap-6">
                    <button
                        v-for="tab in tabs" :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition',
                            activeTab === tab.key
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        {{ tab.label }}
                        <span :class="[
                            'ml-2 px-2 py-0.5 text-xs rounded-full font-semibold',
                            activeTab === tab.key ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'
                        ]">{{ tab.count }}</span>
                    </button>
                </nav>
            </div>

            <!-- ===================== PLATFORM ADMINISTRATORS TAB ===================== -->
            <template v-if="activeTab === 'admins'">
                <!-- Create admin (super admin only) -->
                <div v-if="isSuperAdmin" class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Create Admin Account</h2>
                    <p class="text-sm text-gray-500 mb-5">Provision a new sub-admin to help govern the platform.</p>
                    <form @submit.prevent="submitAdmin" class="max-w-xl space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" v-model="adminForm.name" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
                                <p v-if="adminForm.errors.name" class="text-red-500 text-xs mt-1">{{ adminForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" v-model="adminForm.email" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
                                <p v-if="adminForm.errors.email" class="text-red-500 text-xs mt-1">{{ adminForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input type="password" v-model="adminForm.password" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" v-model="adminForm.password_confirmation" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="adminForm.processing"
                                class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50">
                                {{ adminForm.processing ? 'Creating...' : 'Create Admin' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Admin list -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Platform Administrators</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left">Name</th>
                                <th class="px-6 py-3 text-left">Email</th>
                                <th class="px-6 py-3 text-center">Level</th>
                                <th class="px-6 py-3 text-right">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="admin in admins" :key="admin.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ admin.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ admin.email }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="admin.role === 'super_admin'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Super Admin</span>
                                    <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Sub-Admin</span>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-500">{{ formatDate(admin.created_at) }}</td>
                            </tr>
                            <tr v-if="!admins.length">
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">No administrators found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- ===================== SHOPS / DISTRIBUTORS TAB ===================== -->
            <template v-else-if="activeTab === 'distributors'">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Shops & Distributors</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left">Company</th>
                                <th class="px-6 py-3 text-left">Owner</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-right">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="d in distributors" :key="d.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ d.company_name }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    <div>{{ d.owner_name }}</div>
                                    <div class="text-xs text-gray-400">{{ d.owner_email }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-green-100 text-green-700': d.status === 'approved',
                                        'bg-amber-100 text-amber-700': d.status === 'pending',
                                        'bg-red-100 text-red-700': d.status === 'rejected',
                                    }" class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">
                                        {{ d.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-500">{{ d.created_at }}</td>
                            </tr>
                            <tr v-if="!distributors.length">
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">No distributors registered yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- ===================== PLATFORM USERS TAB ===================== -->
            <template v-else-if="activeTab === 'users'">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Platform Users (Customers & Couriers)</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left">Name</th>
                                <th class="px-6 py-3 text-left">Email</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3 text-right">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="u in platformUsers" :key="u.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ u.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ u.email }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="{
                                        'bg-sky-100 text-sky-700': u.role === 'customer',
                                        'bg-orange-100 text-orange-700': u.role === 'courier',
                                    }" class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-500">{{ formatDate(u.created_at) }}</td>
                            </tr>
                            <tr v-if="!platformUsers.length">
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    admins:        Array,
    distributors:  Array,
    platformUsers: Array,
    isSuperAdmin:  Boolean,
});

const activeTab = ref(props.isSuperAdmin ? 'admins' : 'distributors');

const tabs = computed(() => [
    // Only super_admin can see the Administrators tab
    ...(props.isSuperAdmin ? [{ key: 'admins', label: 'Administrators', count: props.admins?.length ?? 0 }] : []),
    { key: 'distributors', label: 'Shops / Distributors', count: props.distributors?.length ?? 0 },
    { key: 'users',        label: 'Platform Users',       count: props.platformUsers?.length ?? 0 },
]);

const adminForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitAdmin = () => {
    adminForm.post(route('admin.users.store'), {
        onSuccess: () => adminForm.reset(),
        onFinish:  () => adminForm.reset('password', 'password_confirmation'),
    });
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
</script>
