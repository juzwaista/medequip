<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-ink">Platform Staff</h1>
                    <p class="text-ink-soft mt-2">Manage Administrator accounts for MedEquip</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Create Admin Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-card shadow-md p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-ink mb-6">Add New Admin</h2>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-ink mb-1">Full Name</label>
                                <input 
                                    v-model="form.name"
                                    type="text" 
                                    class="w-full rounded-control border-line focus:border-brand focus:ring-brand shadow-sm"
                                    required
                                >
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-ink mb-1">Email Address</label>
                                <input 
                                    v-model="form.email"
                                    type="email" 
                                    class="w-full rounded-control border-line focus:border-brand focus:ring-brand shadow-sm"
                                    required
                                >
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-ink mb-1">
                                Assign Role
                                <span class="text-xs text-ink-faint font-normal ml-1">(can be changed later)</span>
                            </label>
                                <select 
                                    v-model="form.role_id"
                                    class="w-full rounded-control border-line focus:border-brand focus:ring-brand shadow-sm"
                                >
                                    <option value="" disabled>Select Role...</option>
                                    <option v-for="role in platformRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                                <p v-if="form.errors.role_id" class="mt-1 text-sm text-red-600">{{ form.errors.role_id }}</p>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full bg-brand text-white font-semibold py-3 rounded-card shadow-lg hover:bg-brand-dark transition active:scale-95 disabled:opacity-50   text-[11px]"
                            >
                                {{ form.processing ? 'Sending...' : 'Send Invitation' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of Admins -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-card shadow-md overflow-hidden">
                        <div class="p-6 border-b border-line">
                            <h2 class="text-xl font-bold text-ink">Current Admins</h2>
                        </div>
                        
                        <div v-if="admins.length > 0" class="divide-y divide-line">
                            <div v-for="admin in admins" :key="admin.id" class="p-6 hover:bg-mist transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="h-10 w-10 bg-gradient-to-br from-brand to-brand rounded-full flex items-center justify-center text-white font-bold">
                                            {{ admin.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-ink">{{ admin.name }}</p>
                                            <p class="text-sm text-ink-soft">{{ admin.email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div v-if="admin.role !== 'super_admin'" class="flex items-center gap-2">
                                            <select
                                                :value="admin.roles?.[0]?.id ?? ''"
                                                @change="assignRole(admin.id, $event.target.value)"
                                                class="border border-line rounded-control px-3 py-1 text-xs font-semibold text-ink focus:ring-2 focus:ring-brand focus:border-transparent bg-white shadow-sm"
                                            >
                                                <option value="">No custom role</option>
                                                <option v-for="r in platformRoles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                            </select>
                                        </div>
                                        <span :class="admin.role === 'super_admin' ? 'bg-brand-tint text-brand-dark' : 'bg-brand-tint text-brand-dark'" class="px-3 py-1.5 rounded-full text-xs  font-bold ">
                                            {{ admin.role.replace('_', ' ') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-12 text-center text-ink-soft">
                            <svg class="h-12 w-12 text-ink-faint mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <p>No other administrators found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    admins: Array,
    platformRoles: { type: Array, default: () => [] }
});

const form = useForm({
    name: '',
    email: '',
    role_id: '',
});

const submit = () => {
    form.post(route('superadmin.staff.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'email', 'role_id');
        },
    });
};

const assignRole = (userId, roleId) => {
    if (!roleId) {
        router.delete(route('superadmin.staff.removeRole', userId), { preserveScroll: true });
    } else {
        router.post(route('superadmin.staff.assignRole', userId), { role_id: roleId }, { preserveScroll: true });
    }
};
</script>
