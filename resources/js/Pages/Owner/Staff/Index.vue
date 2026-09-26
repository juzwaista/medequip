<template>
    <Head title="Staff Management" />
    <OwnerLayout title="Staff Management">
        <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-ink tracking-tight">Staff Management</h1>
                    <p class="text-sm text-ink-soft mt-1">Manage your team members and configure role access.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="activeTab === 'staff'" @click="openStaffDrawer()" class="bg-brand text-white px-4 py-2 rounded-control text-sm font-bold shadow-sm hover:bg-brand-dark transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Invite Staff
                    </button>
                    <button v-if="activeTab === 'roles'" @click="openRoleDrawer()" class="bg-brand text-white px-4 py-2 rounded-control text-sm font-bold shadow-sm hover:bg-brand-dark transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Create Role
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-line">
                <nav class="-mb-px flex gap-6 overflow-x-auto">
                    <button
                        @click="activeTab = 'staff'"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap',
                            activeTab === 'staff' 
                                ? 'border-brand text-brand' 
                                : 'border-transparent text-ink-soft hover:text-ink hover:border-line'
                        ]"
                    >
                        Staff Accounts
                    </button>
                    <button
                        @click="activeTab = 'roles'"
                        :class="[
                            'pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap',
                            activeTab === 'roles' 
                                ? 'border-brand text-brand' 
                                : 'border-transparent text-ink-soft hover:text-ink hover:border-line'
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
                    <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                        <div class="p-4 border-b border-line bg-mist/50 flex justify-end">
                            <div class="relative w-full sm:w-64">
                                <input
                                    type="text"
                                    v-model="staffSearch"
                                    class="w-full rounded-control border border-line py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-brand focus:border-transparent"
                                    placeholder="Search staff..."
                                />
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                        
                        <table class="w-full text-left text-sm">
                            <thead class="bg-mist text-xs text-ink-soft   border-b border-line font-bold">
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="staff in filteredStaff" :key="staff.id" class="hover:bg-mist/50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 bg-brand-tint text-brand-dark rounded-full flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ getInitials(staff.name) }}
                                            </div>
                                            <span class="font-bold text-ink">{{ staff.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-ink-soft">{{ staff.email }}</td>
                                    <td class="px-6 py-4">
                                        <span v-if="staff.roles && staff.roles.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold  bg-brand-tint text-brand-dark border border-brand-soft">
                                            {{ staff.roles[0].name }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold  bg-mist text-ink-soft border border-line">
                                            Custom
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="staff.email_verified_at" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold  bg-brand-tint text-brand-dark border border-brand-soft">
                                            Active
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold  bg-yellow-50 text-yellow-700 border border-yellow-200">
                                            Pending
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeStaff(staff.id)" class="text-red-500 hover:text-red-700 font-semibold text-sm">Remove</button>
                                    </td>
                                </tr>
                                <tr v-if="!filteredStaff.length">
                                    <td colspan="5" class="px-6 py-10 text-center text-ink-faint">No staff members found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ROLE TEMPLATES TAB -->
                <div v-show="activeTab === 'roles'">
                    <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                        <div class="p-4 border-b border-line bg-mist/50 flex justify-end">
                            <div class="relative w-full sm:w-64">
                                <input
                                    type="text"
                                    v-model="roleSearch"
                                    class="w-full rounded-control border border-line py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-brand focus:border-transparent"
                                    placeholder="Search roles..."
                                />
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>

                        <table class="w-full text-left text-sm">
                            <thead class="bg-mist text-xs text-ink-soft   border-b border-line font-bold">
                                <tr>
                                    <th class="px-6 py-4">Role Name</th>
                                    <th class="px-6 py-4">Permissions</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="role in filteredRoles" :key="role.id" class="hover:bg-mist/50 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-ink">{{ role.name }}</span>
                                        <p v-if="role.description" class="text-xs text-ink-soft mt-1 max-w-xs truncate">{{ role.description }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1.5 max-w-md">
                                            <span v-for="perm in role.permissions?.slice(0, 3)" :key="perm.id" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-tint text-brand-dark border border-brand-soft">
                                                {{ formatPermissionName(perm.name) }}
                                            </span>
                                            <span v-if="role.permissions?.length > 3" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-mist text-ink-soft">
                                                +{{ role.permissions.length - 3 }} more
                                            </span>
                                            <span v-if="!role.permissions?.length" class="text-xs text-ink-faint italic">None</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openRoleDrawer(role)" class="text-brand hover:text-brand-dark font-semibold text-sm">Edit</button>
                                        <button @click="deleteRole(role)" class="text-red-500 hover:text-red-700 font-semibold text-sm">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="!filteredRoles.length">
                                    <td colspan="3" class="px-6 py-10 text-center text-ink-faint">No roles found. Create one to get started.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>


            <!-- STAFF INVITATION MODAL -->
            <Teleport to="body">
                <div v-if="staffDrawer.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                    <div class="fixed inset-0 bg-ink/60  transition-opacity" @click="closeStaffDrawer()"></div>
                    
                    <div class="bg-white rounded-card shadow-xl w-full max-w-md relative z-10 flex flex-col">
                        <div class="px-6 py-4 border-b border-line flex items-center justify-between bg-mist rounded-t-card">
                            <h2 class="text-xl font-semibold text-ink">Invite Staff Member</h2>
                            <button @click="closeStaffDrawer()" class="text-ink-faint hover:text-ink-soft bg-white p-2 rounded-full hover:bg-mist transition shadow-sm border border-line">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="p-6">
                            <form @submit.prevent="submitStaff" class="space-y-5">
                                <div>
                                    <label class="block text-xs font-bold text-ink   mb-2">Email Address</label>
                                    <input type="email" v-model="staffForm.email" required placeholder="staff@example.com" class="w-full border border-line rounded-control p-3 text-sm focus:ring-2 focus:ring-brand focus:border-brand transition">
                                    <p v-if="staffForm.errors.email" class="mt-1 text-xs text-red-600">{{ staffForm.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-ink   mb-2">Assign Role Template</label>
                                    <select v-model="staffDrawer.selectedTemplate" @change="applyRoleTemplateToStaff" required class="w-full border border-line rounded-control p-3 text-sm focus:ring-2 focus:ring-brand focus:border-brand transition">
                                        <option value="" disabled>Select Role...</option>
                                        <option v-for="r in shopRoles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                    </select>
                                </div>
                                <button type="submit" :disabled="staffForm.processing" class="w-full bg-brand text-white font-bold py-3 rounded-control shadow-sm hover:bg-brand-dark transition disabled:opacity-50 mt-4">
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
                    <div class="fixed inset-0 bg-ink/60  transition-opacity" @click="closeRoleDrawer()"></div>
                    
                    <div class="bg-white rounded-card shadow-xl w-full max-w-4xl relative z-10 flex flex-col max-h-[90vh]">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-line flex items-center justify-between shrink-0 bg-mist rounded-t-card">
                            <div>
                                <h2 class="text-xl font-semibold text-ink">{{ roleDrawer.isEdit ? 'Edit Role Template' : 'Create Role Template' }}</h2>
                                <p class="text-sm text-ink-soft mt-1">Configure permissions for your staff.</p>
                            </div>
                            <button @click="closeRoleDrawer()" class="text-ink-faint hover:text-ink-soft bg-white p-2 rounded-full hover:bg-mist transition shadow-sm border border-line">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="p-6 overflow-y-auto space-y-6">
                            <form @submit.prevent="submitRole" id="role-form" class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-ink   mb-2">Role Name</label>
                                    <input type="text" v-model="roleForm.name" required placeholder="e.g. Warehouse Staff" class="w-full border border-line rounded-control p-3 text-sm focus:ring-2 focus:ring-brand focus:border-brand transition">
                                    <p v-if="roleForm.errors.name" class="mt-1 text-xs text-red-600">{{ roleForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-ink   mb-2">Description <span class="text-ink-faint font-normal normal-case tracking-normal">(Optional)</span></label>
                                    <textarea v-model="roleForm.description" rows="2" placeholder="Briefly describe this role's purpose..." class="w-full border border-line rounded-control p-3 text-sm focus:ring-2 focus:ring-brand focus:border-brand transition"></textarea>
                                    <p v-if="roleForm.errors.description" class="mt-1 text-xs text-red-600">{{ roleForm.errors.description }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-ink   mb-3">Permissions</label>
                                    <div class="space-y-4">
                                        <div v-for="(perms, group) in permissions" :key="group" class="border border-line rounded-card overflow-hidden">
                                            <div class="flex items-center justify-between px-4 py-3 bg-mist border-b border-line">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-brand"></span>
                                                    <h4 class="text-sm font-bold text-ink capitalize">{{ group }}</h4>
                                                </div>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-brand hover:text-brand-dark transition">
                                                    <input type="checkbox"
                                                        :checked="perms.every(p => roleForm.permissions.includes(p.name))"
                                                        @change="toggleGroupPermissions($event, perms, roleForm.permissions)"
                                                        class="rounded border-line text-brand focus:ring-brand h-3.5 w-3.5"
                                                    >
                                                    Select All
                                                </label>
                                            </div>
                                            <div class="grid sm:grid-cols-2 gap-0 divide-x divide-y divide-line">
                                                <label v-for="p in perms" :key="p.id" class="flex items-center gap-3 p-4 cursor-pointer hover:bg-brand-tint/50 transition">
                                                    <input type="checkbox" :value="p.name" v-model="roleForm.permissions" class="rounded border-line text-brand focus:ring-brand h-4 w-4 shrink-0">
                                                    <span class="text-sm font-semibold text-ink capitalize block">{{ formatPermissionName(p.name) }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-line bg-mist flex justify-end gap-3 shrink-0 rounded-b-card">
                            <button type="button" @click="closeRoleDrawer()" class="px-4 py-2 bg-white border border-line rounded-control text-sm font-bold text-ink hover:bg-mist transition shadow-sm">
                                Cancel
                            </button>
                            <button type="submit" form="role-form" :disabled="roleForm.processing" class="px-4 py-2 bg-brand text-white rounded-control text-sm font-bold shadow-sm hover:bg-brand-dark transition disabled:opacity-50">
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
