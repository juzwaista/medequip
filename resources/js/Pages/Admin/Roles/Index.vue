<template>
    <Head title="Platform Roles" />
    <AdminLayout title="Role Management">
        <div class="max-w-7xl mx-auto py-6">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Platform Roles</h2>
                    <p class="text-sm text-gray-500">Manage internal MedEquip staff roles and access levels.</p>
                </div>
                <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition">
                    + Create Role
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-xs font-bold">
                        <tr>
                            <th class="px-6 py-4">Role Name</th>
                            <th class="px-6 py-4">Permissions Count</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 font-bold text-gray-900">
                                {{ role.name }}
                                <span v-if="role.name === 'Super Admin'" class="ml-2 px-2 py-0.5 rounded text-[10px] bg-red-100 text-red-800 uppercase">Core</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ role.permissions?.length || 0 }} permissions
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button v-if="role.name !== 'Super Admin'" @click="openModal(role)" class="text-blue-600 hover:text-blue-800 font-semibold mr-3">Edit</button>
                                <button v-if="role.name !== 'Super Admin'" @click="deleteRole(role)" class="text-red-600 hover:text-red-800 font-semibold">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!roles.length">
                            <td colspan="3" class="px-6 py-8 text-center text-gray-400">No custom roles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Role Modal -->
            <Teleport to="body">
                <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h3 class="text-lg font-bold text-gray-900">{{ modal.role ? 'Edit Role' : 'Create Role' }}</h3>
                            <button @click="modal.open = false" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        
                        <div class="p-6 overflow-y-auto">
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Role Name</label>
                                <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. Compliance Officer" required>
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-3">Assign Permissions</label>
                                <div class="space-y-6">
                                    <div v-for="(perms, group) in permissions" :key="group" class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 border-b border-gray-200 pb-2">{{ group }}</h4>
                                        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                                            <label v-for="p in perms" :key="p.id" class="flex items-start gap-2 cursor-pointer">
                                                <input type="checkbox" :value="p.name" v-model="form.permissions" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm text-gray-700 select-none">{{ p.name }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                            <button @click="modal.open = false" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                            <button @click="submitRole" :disabled="form.processing" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50">
                                Save Role
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    roles: Array,
    permissions: Object,
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
    if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
        router.delete(`/admin/roles/${role.id}`);
    }
};
</script>
