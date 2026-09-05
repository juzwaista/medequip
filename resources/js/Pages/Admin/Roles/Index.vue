<template>
    <Head title="Platform Roles" />
    <AdminLayout title="Role Management">
        <div class="max-w-5xl mx-auto py-6 space-y-6">

            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Platform Roles</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage internal MedEquip staff roles and their granular access permissions.</p>
                </div>
                <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Create Role
                </button>
            </div>

            <!-- Roles Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-xs font-bold">
                        <tr>
                            <th class="px-6 py-4">Role Name</th>
                            <th class="px-6 py-4">Permissions</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900">{{ role.name }}</span>
                                    <span v-if="role.name === 'Super Admin'" class="px-2 py-0.5 rounded text-[10px] bg-purple-100 text-purple-800 uppercase font-bold">Core</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="perm in (role.permissions ?? []).slice(0, 5)"
                                        :key="perm.id"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100"
                                    >
                                        {{ formatPermLabel(perm.name) }}
                                    </span>
                                    <span
                                        v-if="(role.permissions ?? []).length > 5"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600"
                                    >
                                        +{{ (role.permissions ?? []).length - 5 }} more
                                    </span>
                                    <span v-if="!(role.permissions ?? []).length" class="text-gray-400 text-xs italic">No permissions</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button v-if="role.name !== 'Super Admin'" @click="openModal(role)" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Edit</button>
                                <button v-if="role.name !== 'Super Admin'" @click="deleteRole(role)" class="text-red-500 hover:text-red-700 font-semibold text-sm">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!roles.length">
                            <td colspan="3" class="px-6 py-10 text-center text-gray-400">No custom roles found. Create one to get started.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Role Modal -->
        <Teleport to="body">
            <div v-if="modal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="modal.open = false"></div>
                
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl relative z-10 flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-gray-50 rounded-t-2xl">
                        <div>
                            <h2 class="text-xl font-black text-gray-900">{{ modal.role ? 'Edit Platform Role' : 'Create Platform Role' }}</h2>
                            <p class="text-sm text-gray-500 mt-1">Configure permissions for your administrative staff.</p>
                        </div>
                        <button @click="modal.open = false" class="text-gray-400 hover:text-gray-600 bg-white p-2 rounded-full hover:bg-gray-100 transition shadow-sm border border-gray-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-6">

                        <!-- Role Name -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Role Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                placeholder="e.g. Applications Reviewer, Dispute Handler"
                                required
                            >
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Permissions — Grouped by Category -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Assign Permissions</label>

                            <!-- No permissions fallback -->
                            <div v-if="permissions.length === 0" class="rounded-xl border border-dashed border-gray-300 py-8 text-center text-gray-400 text-sm">
                                No permissions available. Make sure permissions are seeded in the database.
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="item in permissions"
                                    :key="item.group"
                                    class="border border-gray-200 rounded-xl overflow-hidden"
                                >
                                    <!-- Group Header -->
                                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                                        <div class="flex items-center gap-2">
                                            <span :class="groupColor(item.group)" class="w-2.5 h-2.5 rounded-full"></span>
                                            <h4 class="text-sm font-bold text-gray-700 capitalize">{{ item.group }}</h4>
                                            <span class="text-xs text-gray-400">({{ item.perms.length }} permission{{ item.perms.length > 1 ? 's' : '' }})</span>
                                        </div>
                                        <!-- Select all for group -->
                                        <button
                                            type="button"
                                            @click="toggleGroup(item.perms)"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition"
                                        >
                                            {{ isGroupAllSelected(item.perms) ? 'Deselect all' : 'Select all' }}
                                        </button>
                                    </div>

                                    <!-- Permissions in Group -->
                                    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-0 divide-x divide-y divide-gray-100">
                                        <label
                                            v-for="p in item.perms"
                                            :key="p.id"
                                            class="flex items-start gap-3 p-4 cursor-pointer hover:bg-blue-50/50 transition"
                                            :class="{ 'bg-blue-50': form.permissions.includes(p.name) }"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="p.name"
                                                v-model="form.permissions"
                                                class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0"
                                            >
                                            <div>
                                                <span class="text-sm font-semibold text-gray-800 block capitalize">{{ formatPermAction(p.name) }}</span>
                                                <span class="text-[11px] text-gray-400">{{ p.name }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Selection Count -->
                            <p class="mt-3 text-xs text-gray-500">
                                <span class="font-bold text-blue-600">{{ form.permissions.length }}</span> permission{{ form.permissions.length !== 1 ? 's' : '' }} selected
                            </p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center shrink-0">
                        <button
                            v-if="form.permissions.length > 0"
                            type="button"
                            @click="form.permissions = []"
                            class="text-xs text-gray-500 hover:text-gray-700 font-semibold transition"
                        >
                            Clear all
                        </button>
                        <div v-else></div>
                        <div class="flex gap-2">
                            <button @click="modal.open = false" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                            <button @click="submitRole" :disabled="form.processing" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ modal.role ? 'Save Changes' : 'Create Role' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const modal = reactive({
    open: false,
    role: null,
});

const form = useForm({
    name: '',
    permissions: [],
});

const openModal = (role = null) => {
    modal.role = role;
    if (role) {
        form.name = role.name;
        form.permissions = role.permissions ? role.permissions.map(p => p.name) : [];
    } else {
        form.reset();
        form.clearErrors();
    }
    modal.open = true;
};

const submitRole = () => {
    if (modal.role) {
        form.put(`/admin/roles/${modal.role.id}`, {
            onSuccess: () => modal.open = false,
        });
    } else {
        form.post('/admin/roles', {
            onSuccess: () => modal.open = false,
        });
    }
};

const deleteRole = (role) => {
    if (confirm(`Are you sure you want to delete the role "${role.name}"? Admins assigned this role will lose its permissions.`)) {
        router.delete(`/admin/roles/${role.id}`);
    }
};

// Toggle all permissions in a group
const toggleGroup = (perms) => {
    const names = perms.map(p => p.name);
    const allSelected = names.every(n => form.permissions.includes(n));
    if (allSelected) {
        form.permissions = form.permissions.filter(p => !names.includes(p));
    } else {
        const toAdd = names.filter(n => !form.permissions.includes(n));
        form.permissions.push(...toAdd);
    }
};

const isGroupAllSelected = (perms) => {
    return perms.every(p => form.permissions.includes(p.name));
};

// e.g. 'admin.applications.review' → 'Review'
const formatPermAction = (name) => {
    const parts = name.split('.');
    const action = parts[parts.length - 1] ?? name;
    return action.charAt(0).toUpperCase() + action.slice(1);
};

// e.g. 'admin.applications.review' → 'Applications: Review'
const formatPermLabel = (name) => {
    const parts = name.split('.');
    if (parts.length === 3) {
        return `${parts[1].charAt(0).toUpperCase() + parts[1].slice(1)}: ${parts[2]}`;
    }
    return name;
};

// Color dot per group
const groupColor = (group) => {
    const colors = {
        applications: 'bg-amber-400',
        orders: 'bg-blue-400',
        products: 'bg-emerald-400',
        couriers: 'bg-indigo-400',
        disputes: 'bg-rose-400',
    };
    return colors[group] ?? 'bg-gray-400';
};
</script>
