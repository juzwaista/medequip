<template>
    <Head title="Staff Management" />
    <OwnerLayout title="Staff Management">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Staff Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your team members and configure role access.</p>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        @click="activeTab = 'staff'"
                        :class="[
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                            activeTab === 'staff' 
                                ? 'border-gray-900 text-gray-900' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Staff Accounts
                    </button>
                    <button
                        @click="activeTab = 'roles'"
                        :class="[
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                            activeTab === 'roles' 
                                ? 'border-gray-900 text-gray-900' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Role Templates
                    </button>
                </nav>
            </div>

            <!-- TAB CONTENT CONTAINER -->
            <div class="min-h-[700px] relative">
                
                <!-- STAFF ACCOUNTS TAB -->
                <div v-show="activeTab === 'staff'">
                    <!-- Action Bar -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div class="flex flex-1 gap-3 w-full sm:w-auto">
                            <div class="relative max-w-sm w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="staffSearch"
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900"
                                    placeholder="Search by name or email..."
                                />
                            </div>
                            <!-- Future filter dropdowns could go here -->
                        </div>
                        <button
                            @click="openStaffDrawer()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 w-full sm:w-auto"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Staff Member
                        </button>
                    </div>

                    <!-- Staff Data Table -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Staff Member</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned Role</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Permissions Summary</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template v-if="filteredStaff.length > 0">
                                        <tr v-for="staff in filteredStaff" :key="staff.id" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 font-bold text-sm border border-gray-200">
                                                        {{ getInitials(staff.name) }}
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">{{ staff.name }}</div>
                                                        <div class="text-sm text-gray-500">{{ staff.email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span v-if="staff.roles && staff.roles.length > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                    {{ staff.roles[0].name }}
                                                </span>
                                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                    Custom
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 truncate max-w-xs" :title="staff.permissions.map(p => formatPermissionName(p.name)).join(', ')">
                                                    {{ staff.permissions.length > 0 ? staff.permissions.length + ' permissions' : 'No access' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span v-if="staff.email_verified_at" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-500" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                                    Active
                                                </span>
                                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">
                                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-yellow-500" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                                    Pending
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                                <!-- Edit removed for now per initial spec focusing on Create, can be added easily -->
                                                <button @click="removeStaff(staff.id)" class="text-red-500 hover:text-red-700 transition-colors">Revoke Access</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="5" class="py-24 px-6 text-center">
                                            <svg class="mx-auto h-16 w-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <h3 class="mt-4 text-base font-medium text-gray-900">No staff members found</h3>
                                            <p class="mt-1 text-sm text-gray-500">You haven't added any team members yet.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ROLE TEMPLATES TAB -->
                <div v-show="activeTab === 'roles'">
                    <!-- Action Bar -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div class="flex flex-1 gap-3 w-full sm:w-auto">
                            <div class="relative max-w-sm w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="roleSearch"
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900"
                                    placeholder="Search role templates..."
                                />
                            </div>
                        </div>
                        <button
                            @click="openRoleDrawer()"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 w-full sm:w-auto"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create Role
                        </button>
                    </div>

                    <!-- Role Data Table -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role Name</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Included Permissions</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Users</th>
                                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template v-if="filteredRoles.length > 0">
                                        <tr v-for="role in filteredRoles" :key="role.id" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ role.name }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 max-w-xs truncate">{{ role.description || 'No description provided.' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-wrap gap-1.5 max-w-sm">
                                                    <span v-for="perm in role.permissions?.slice(0, 3)" :key="perm.id" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                        {{ formatPermissionName(perm.name) }}
                                                    </span>
                                                    <span v-if="role.permissions?.length > 3" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                                        +{{ role.permissions.length - 3 }} more
                                                    </span>
                                                    <span v-if="!role.permissions?.length" class="text-xs text-gray-400 italic">None</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                —
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                                <button @click="openRoleDrawer(role)" class="text-gray-900 hover:text-gray-600 transition-colors">Edit</button>
                                                <button @click="deleteRole(role)" class="text-red-500 hover:text-red-700 transition-colors">Delete</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="5" class="py-24 px-6 text-center">
                                            <svg class="mx-auto h-16 w-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            <h3 class="mt-4 text-base font-medium text-gray-900">No roles found</h3>
                                            <p class="mt-1 text-sm text-gray-500">Create reusable roles to quickly assign permissions.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

                    <!-- Staff Modal -->
            <Teleport to="body">
                <div v-if="staffDrawer.open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" @click="closeStaffDrawer()"></div>
                        
                        <!-- This element is to trick the browser into centering the modal contents. -->
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        
                        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">Add Staff Member</h2>
                                <button @click="closeStaffDrawer()" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            
                            <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                                <form @submit.prevent="submitStaff" id="staff-form" class="space-y-8">
                                    

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                            <input type="email" v-model="staffForm.email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                                            <p v-if="staffForm.errors.email" class="mt-1 text-xs text-red-600">{{ staffForm.errors.email }}</p>
                                        </div>


                                    <hr class="border-gray-100">

                                    <!-- Role Selection -->
                                    <div class="space-y-4">
                                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">Role & Access</h3>
                                        <div class="space-y-3">
                                            <label class="flex items-start">
                                                <input type="radio" v-model="staffDrawer.roleType" value="template" class="mt-0.5 h-4 w-4 text-gray-900 focus:ring-gray-900 border-gray-300">
                                                <div class="ml-3">
                                                    <span class="block text-sm font-medium text-gray-900">Select Role</span>
                                                </div>
                                            </label>
                                            <div v-if="staffDrawer.roleType === 'template'" class="ml-7 mt-2">
                                                <select v-model="staffDrawer.selectedTemplate" @change="applyRoleTemplateToStaff" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                                                    <option value="" disabled>Select a role...</option>
                                                    <option v-for="r in shopRoles" :key="r.id" :value="r.id">{{ r.name }}</option>
                                                </select>
                                            </div>

                                            <label class="flex items-start">
                                                <input type="radio" v-model="staffDrawer.roleType" value="custom" @change="staffForm.permissions = []" class="mt-0.5 h-4 w-4 text-gray-900 focus:ring-gray-900 border-gray-300">
                                                <div class="ml-3">
                                                    <span class="block text-sm font-medium text-gray-900">Custom Permissions</span>
                                                </div>
                                            </label>
                                        </div>

                                        <!-- Custom Permissions Checkboxes -->
                                        <div v-if="staffDrawer.roleType === 'custom'" class="ml-7 mt-4 space-y-5">
                                            <div v-for="(perms, group) in permissions" :key="group">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-semibold text-gray-500 uppercase">{{ group }}</span>
                                                    <input type="checkbox"
                                                        :checked="perms.every(p => staffForm.permissions.includes(p.name))"
                                                        @change="toggleGroupPermissions($event, perms, staffForm.permissions)"
                                                        class="rounded-sm border-gray-300 text-gray-900 focus:ring-gray-900 h-3.5 w-3.5 cursor-pointer"
                                                        title="Toggle all"
                                                    >
                                                </div>
                                                <div class="space-y-2">
                                                    <label v-for="p in perms" :key="p.id" class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" :value="p.name" v-model="staffForm.permissions" class="rounded-sm border-gray-300 text-gray-900 focus:ring-gray-900 h-4 w-4">
                                                        <span class="text-sm text-gray-700 capitalize">{{ formatPermissionName(p.name) }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </form>
                            </div>
                            
                            <!-- Sticky Footer -->
                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-xl">
                                <button type="button" @click="closeStaffDrawer()" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" form="staff-form" :disabled="staffForm.processing" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 disabled:opacity-50">
                                    {{ staffForm.processing ? 'Sending...' : 'Send Invite' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ROLE MODAL -->
            <Teleport to="body">
                <div v-if="roleDrawer.open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" @click="closeRoleDrawer()"></div>
                        
                        <!-- This element is to trick the browser into centering the modal contents. -->
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        
                        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">{{ roleDrawer.isEdit ? 'Edit Role' : 'Create Role' }}</h2>
                                <button @click="closeRoleDrawer()" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            
                            <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                                <form @submit.prevent="submitRole" id="role-form" class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Role Name</label>
                                        <input type="text" v-model="roleForm.name" required placeholder="e.g. Warehouse Staff" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                                        <p v-if="roleForm.errors.name" class="mt-1 text-xs text-red-600">{{ roleForm.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400 font-normal">(Optional)</span></label>
                                        <textarea v-model="roleForm.description" rows="2" placeholder="Briefly describe this role's purpose..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-gray-900 focus:border-gray-900 sm:text-sm"></textarea>
                                        <p v-if="roleForm.errors.description" class="mt-1 text-xs text-red-600">{{ roleForm.errors.description }}</p>
                                    </div>
                                    
                                    <div class="pt-4 border-t border-gray-100">
                                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Permissions</h3>
                                        <div class="space-y-6">
                                            <div v-for="(perms, group) in permissions" :key="group" class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                                <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
                                                    <span class="text-sm font-bold text-gray-700 uppercase">{{ group }}</span>
                                                    <label class="flex items-center gap-1.5 cursor-pointer text-xs font-medium text-gray-500 hover:text-gray-900">
                                                        <input type="checkbox"
                                                            :checked="perms.every(p => roleForm.permissions.includes(p.name))"
                                                            @change="toggleGroupPermissions($event, perms, roleForm.permissions)"
                                                            class="rounded-sm border-gray-300 text-gray-900 focus:ring-gray-900 h-3.5 w-3.5"
                                                        >
                                                        Select All
                                                    </label>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <label v-for="p in perms" :key="p.id" class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" :value="p.name" v-model="roleForm.permissions" class="rounded-sm border-gray-300 text-gray-900 focus:ring-gray-900 h-4 w-4">
                                                        <span class="text-sm text-gray-700 capitalize">{{ formatPermissionName(p.name) }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Sticky Footer -->
                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-xl">
                                <button type="button" @click="closeRoleDrawer()" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" form="role-form" :disabled="roleForm.processing" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 disabled:opacity-50">
                                    {{ roleForm.processing ? 'Saving...' : 'Save Role' }}
                                </button>
                            </div>
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
    roleType: 'template', // 'template' or 'custom'
    selectedTemplate: ''
});

const staffForm = useForm({
    email: '',
    permissions: [],
});

const openStaffDrawer = () => {
    staffForm.reset();
    staffForm.clearErrors();
    staffDrawer.roleType = 'template';
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
    // If they chose template but didn't select one, they might pass empty permissions, backend handles it
    staffForm.post(route('owner.staff.store'), {
        onSuccess: () => {
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
