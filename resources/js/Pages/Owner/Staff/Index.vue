<template>
    <Head title="Staff Management" />
    <OwnerLayout title="Staff Management">
        <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Staff Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage your team members and configure role access.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="activeTab === 'staff'" @click="openStaffDrawer()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Invite Staff
                    </button>
                    <button v-if="activeTab === 'roles'" @click="openRoleDrawer()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Create Role
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6 overflow-x-auto">
                    <button
                        @click="activeTab = 'staff'"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap',
                            activeTab === 'staff' 
                                ? 'border-blue-600 text-blue-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Staff Accounts
                    </button>
                    <button
                        @click="activeTab = 'roles'"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap',
                            activeTab === 'roles' 
                                ? 'border-blue-600 text-blue-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Role Templates
                    </button>
                </nav>
            </div>

            <!-- TAB CONTENT -->
            <div class="min-h-[500px]">
                
                <!-- STAFF ACCOUNTS TAB -->
                <div v-show="activeTab === 'staff'">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-end">
                            <div class="relative w-full sm:w-64">
                                <input
                                    type="text"
                                    v-model="staffSearch"
                                    class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Search staff..."
                                />
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                        
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100 font-bold">
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="staff in filteredStaff" :key="staff.id" class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ getInitials(staff.name) }}
                                            </div>
                                            <span class="font-bold text-gray-900">{{ staff.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ staff.email }}</td>
                                    <td class="px-6 py-4">
                                        <span v-if="staff.roles && staff.roles.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ staff.roles[0].name }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200">
                                            Custom
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="staff.email_verified_at" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-200">
                                            Active
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-yellow-50 text-yellow-700 border border-yellow-200">
                                            Pending
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeStaff(staff.id)" class="text-red-500 hover:text-red-700 font-semibold text-sm">Remove</button>
                                    </td>
                                </tr>
                                <tr v-if="!filteredStaff.length">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">No staff members found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ROLE TEMPLATES TAB -->
                <div v-show="activeTab === 'roles'">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-end">
                            <div class="relative w-full sm:w-64">
                                <input
                                    type="text"
                                    v-model="roleSearch"
                                    class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Search roles..."
                                />
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>

                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100 font-bold">
                                <tr>
                                    <th class="px-6 py-4">Role Name</th>
                                    <th class="px-6 py-4">Permissions</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="role in filteredRoles" :key="role.id" class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-gray-900">{{ role.name }}</span>
                                        <p v-if="role.description" class="text-xs text-gray-500 mt-1 max-w-xs truncate">{{ role.description }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1.5 max-w-md">
                                            <span v-for="perm in role.permissions?.slice(0, 3)" :key="perm.id" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ formatPermissionName(perm.name) }}
                                            </span>
                                            <span v-if="role.permissions?.length > 3" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600">
                                                +{{ role.permissions.length - 3 }} more
                                            </span>
                                            <span v-if="!role.permissions?.length" class="text-xs text-gray-400 italic">None</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openRoleDrawer(role)" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Edit</button>
                                        <button @click="deleteRole(role)" class="text-red-500 hover:text-red-700 font-semibold text-sm">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="!filteredRoles.length">
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-400">No roles found. Create one to get started.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>


            <!-- STAFF INVITATION MODAL -->
            <Teleport to="body">
                <div v-if="staffDrawer.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeStaffDrawer()"></div>
                    
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative z-10 flex flex-col">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-2xl">
                            <h2 class="text-xl font-black text-gray-900">Invite Staff Member</h2>
                            <button @click="closeStaffDrawer()" class="text-gray-400 hover:text-gray-600 bg-white p-2 rounded-full hover:bg-gray-100 transition shadow-sm border border-gray-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="p-6">
                            <form @submit.prevent="submitStaff" class="space-y-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address</label>
                                    <input type="email" v-model="staffForm.email" required placeholder="staff@example.com" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <p v-if="staffForm.errors.email" class="mt-1 text-xs text-red-600">{{ staffForm.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assign Role Template</label>
                                    <select v-model="staffDrawer.selectedTemplate" @change="applyRoleTemplateToStaff" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                        <option value="" disabled>Select Role...</option>
                                        <option v-for="r in shopRoles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                    </select>
                                </div>
                                <button type="submit" :disabled="staffForm.processing" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-sm hover:bg-blue-700 transition disabled:opacity-50 mt-4">
                                    {{ staffForm.processing ? 'Sending...' : 'Send Invitation' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ROLE MODAL -->
            <Teleport to="body">
                <div v-if="roleDrawer.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeRoleDrawer()"></div>
                    
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl relative z-10 flex flex-col max-h-[90vh]">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-gray-50 rounded-t-2xl">
                            <div>
                                <h2 class="text-xl font-black text-gray-900">{{ roleDrawer.isEdit ? 'Edit Role Template' : 'Create Role Template' }}</h2>
                                <p class="text-sm text-gray-500 mt-1">Configure permissions for your staff.</p>
                            </div>
                            <button @click="closeRoleDrawer()" class="text-gray-400 hover:text-gray-600 bg-white p-2 rounded-full hover:bg-gray-100 transition shadow-sm border border-gray-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="p-6 overflow-y-auto space-y-6">
                            <form @submit.prevent="submitRole" id="role-form" class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Role Name</label>
                                    <input type="text" v-model="roleForm.name" required placeholder="e.g. Warehouse Staff" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <p v-if="roleForm.errors.name" class="mt-1 text-xs text-red-600">{{ roleForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Description <span class="text-gray-400 font-normal normal-case tracking-normal">(Optional)</span></label>
                                    <textarea v-model="roleForm.description" rows="2" placeholder="Briefly describe this role's purpose..." class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                                    <p v-if="roleForm.errors.description" class="mt-1 text-xs text-red-600">{{ roleForm.errors.description }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Permissions</label>
                                    <div class="space-y-4">
                                        <div v-for="(perms, group) in permissions" :key="group" class="border border-gray-200 rounded-xl overflow-hidden">
                                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                                    <h4 class="text-sm font-bold text-gray-700 capitalize">{{ group }}</h4>
                                                </div>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                                                    <input type="checkbox"
                                                        :checked="perms.every(p => roleForm.permissions.includes(p.name))"
                                                        @change="toggleGroupPermissions($event, perms, roleForm.permissions)"
                                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-3.5 w-3.5"
                                                    >
                                                    Select All
                                                </label>
                                            </div>
                                            <div class="grid sm:grid-cols-2 gap-0 divide-x divide-y divide-gray-100">
                                                <label v-for="p in perms" :key="p.id" class="flex items-center gap-3 p-4 cursor-pointer hover:bg-blue-50/50 transition">
                                                    <input type="checkbox" :value="p.name" v-model="roleForm.permissions" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 shrink-0">
                                                    <span class="text-sm font-semibold text-gray-800 capitalize block">{{ formatPermissionName(p.name) }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                            <button type="button" @click="closeRoleDrawer()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 transition shadow-sm">
                                Cancel
                            </button>
                            <button type="submit" form="role-form" :disabled="roleForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold shadow-sm hover:bg-blue-700 transition disabled:opacity-50">
                                {{ roleForm.processing ? 'Saving...' : 'Save Role' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    staffMembers: { type: Array, default: () => [] },
    shopRoles: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
});

const activeTab = ref('staff');

// Search & Filtering
const staffSearch = ref('');
const roleSearch = ref('');

const filteredStaff = computed(() => {
    if (!staffSearch.value) return props.staffMembers;
    const query = staffSearch.value.toLowerCase();
    return props.staffMembers.filter(s => 
        s.name.toLowerCase().includes(query) || 
        s.email.toLowerCase().includes(query)
    );
});

const filteredRoles = computed(() => {
    if (!roleSearch.value) return props.shopRoles;
    const query = roleSearch.value.toLowerCase();
    return props.shopRoles.filter(r => r.name.toLowerCase().includes(query));
});

// Helpers
const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const formatPermissionName = (name) => {
    return name.replace('shop.', '').replace(/-/g, ' ');
};

const toggleGroupPermissions = (event, permsInGroup, targetArray) => {
    const isChecked = event.target.checked;
    permsInGroup.forEach(p => {
        const idx = targetArray.indexOf(p.name);
        if (isChecked && idx === -1) {
            targetArray.push(p.name);
        } else if (!isChecked && idx !== -1) {
            targetArray.splice(idx, 1);
        }
    });
};

// --- STAFF DRAWER & LOGIC ---
const staffDrawer = reactive({
    open: false,
    selectedTemplate: ''
});

const staffForm = useForm({
    email: '',
    permissions: [],
});

const openStaffDrawer = () => {
    staffForm.reset();
    staffForm.clearErrors();
    staffDrawer.selectedTemplate = '';
    staffDrawer.open = true;
};

const closeStaffDrawer = () => {
    staffDrawer.open = false;
};

const applyRoleTemplateToStaff = () => {
    if (!staffDrawer.selectedTemplate) {
        staffForm.permissions = [];
        return;
    }
    const role = props.shopRoles.find(r => r.id === staffDrawer.selectedTemplate);
    if (role && role.permissions) {
        staffForm.permissions = role.permissions.map(p => p.name);
    }
};

const submitStaff = () => {
    staffForm.post(route('owner.staff.store'), {
        onSuccess: () => {
            staffForm.reset();
            staffDrawer.selectedTemplate = '';
            closeStaffDrawer();
        }
    });
};

const removeStaff = (id) => {
    if (confirm('Revoke access for this staff member?')) {
        router.delete(route('owner.staff.destroy', id), { preserveScroll: true });
    }
};

// --- ROLE DRAWER & LOGIC ---
const roleDrawer = reactive({
    open: false,
    isEdit: false,
    roleId: null
});

const roleForm = useForm({
    name: '',
    description: '',
    permissions: [],
});

const openRoleDrawer = (role = null) => {
    roleForm.clearErrors();
    if (role) {
        roleDrawer.isEdit = true;
        roleDrawer.roleId = role.id;
        roleForm.name = role.name;
        roleForm.description = role.description || '';
        roleForm.permissions = role.permissions ? role.permissions.map(p => p.name) : [];
    } else {
        roleDrawer.isEdit = false;
        roleDrawer.roleId = null;
        roleForm.reset();
    }
    roleDrawer.open = true;
};

const closeRoleDrawer = () => {
    roleDrawer.open = false;
};

const submitRole = () => {
    if (roleDrawer.isEdit) {
        roleForm.put(route('owner.roles.update', roleDrawer.roleId), {
            onSuccess: () => closeRoleDrawer()
        });
    } else {
        roleForm.post(route('owner.roles.store'), {
            onSuccess: () => closeRoleDrawer()
        });
    }
};

const deleteRole = (role) => {
    if (confirm(`Delete the "${role.name}" template?`)) {
        router.delete(route('owner.roles.destroy', role.id), {
            preserveScroll: true
        });
    }
};
</script>
