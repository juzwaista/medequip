<template>
    <Head title="Shop Roles" />
    <OwnerLayout title="Role Management">
        <div class="max-w-7xl mx-auto py-6">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-ink">Staff Roles</h2>
                    <p class="text-sm text-ink-soft">Create custom roles and assign specific permissions for your shop's staff.</p>
                </div>
                <button @click="openModal()" class="bg-brand text-white px-4 py-2 rounded-control text-sm font-bold shadow-sm hover:bg-brand-dark transition">
                    + Create Custom Role
                </button>
            </div>

            <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-mist border-b border-line text-ink-soft   text-xs font-bold">
                        <tr>
                            <th class="px-6 py-4">Role Name</th>
                            <th class="px-6 py-4">Permissions Count</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-mist/50 transition">
                            <td class="px-6 py-4 font-bold text-ink">
                                {{ role.name }}
                                <span v-if="role.name === 'Owner'" class="ml-2 px-2 py-0.5 rounded text-xs bg-red-100 text-red-800 ">Shop Owner</span>
                            </td>
                            <td class="px-6 py-4 text-ink-soft">
                                {{ role.permissions?.length || 0 }} permissions
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button v-if="role.name !== 'Owner'" @click="openModal(role)" class="text-brand hover:text-brand-dark font-semibold mr-3">Edit</button>
                                <button v-if="role.name !== 'Owner'" @click="deleteRole(role)" class="text-red-600 hover:text-red-800 font-semibold">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!roles.length">
                            <td colspan="3" class="px-6 py-8 text-center text-ink-faint">No custom roles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Role Modal -->
            <Teleport to="body">
                <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/50 ">
                    <div class="bg-white rounded-card shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="px-6 py-4 border-b border-line flex justify-between items-center bg-mist">
                            <h3 class="text-lg font-bold text-ink">{{ modal.role ? 'Edit Role' : 'Create Custom Role' }}</h3>
                            <button @click="modal.open = false" class="text-ink-faint hover:text-ink-soft"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        
                        <div class="p-6 overflow-y-auto">
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-ink  mb-2">Role Name</label>
                                <input v-model="form.name" type="text" class="w-full border border-line rounded-control p-3 text-sm focus:ring-brand focus:border-brand" placeholder="e.g. Warehouse Staff, Returns Manager" required>
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-ink  mb-3">Allowed Actions</label>
                                <div class="space-y-6">
                                    <div v-for="(perms, group) in permissions" :key="group" class="bg-mist p-4 rounded-card border border-line">
                                        <h4 class="text-xs font-bold text-ink-soft   mb-3 border-b border-line pb-2">{{ group }} Permissions</h4>
                                        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                                            <label v-for="p in perms" :key="p.id" class="flex items-start gap-2 cursor-pointer">
                                                <input type="checkbox" :value="p.name" v-model="form.permissions" class="mt-0.5 rounded border-line text-brand focus:ring-brand">
                                                <span class="text-sm text-ink select-none">{{ p.name.replace('shop.', '') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-mist border-t border-line flex justify-end gap-2">
                            <button @click="modal.open = false" class="px-4 py-2 text-sm font-semibold text-ink bg-white border border-line rounded-control hover:bg-mist transition">Cancel</button>
                            <button @click="submitRole" :disabled="form.processing" class="px-4 py-2 text-sm font-bold text-white bg-brand hover:bg-brand-dark rounded-control transition disabled:opacity-50">
                                Save Role
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

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
        form.put(`/owner/roles/${modal.role.id}`, {
            onSuccess: () => modal.open = false,
        });
    } else {
        form.post('/owner/roles', {
            onSuccess: () => modal.open = false,
        });
    }
};

const deleteRole = (role) => {
    if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
        router.delete(`/owner/roles/${role.id}`);
    }
};
</script>
