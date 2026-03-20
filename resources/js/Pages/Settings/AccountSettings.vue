<template>
    <MainLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
                <p class="text-gray-600 mt-2">Manage your account preferences and security</p>
            </div>



            <!-- Error Messages -->
            <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-1">Error updating your information</h3>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Settings -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Profile Information -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Profile Information</h2>
                        
                        <form @submit.prevent="updateProfile">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                                    <input 
                                        v-model="profileForm.name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                    <input 
                                        v-model="profileForm.email"
                                        type="email"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                    <p v-if="user.email_verified_at" class="text-xs text-green-600 mt-1 flex items-center">
                                        <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Verified
                                    </p>
                                    <p v-else class="text-xs text-gray-500 mt-1">Email not verified</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">User Type</label>
                                    <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                                        <span v-if="user.user_type" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-purple-100 text-purple-800': user.user_type === 'admin',
                                                'bg-blue-100 text-blue-800': user.user_type === 'distributor',
                                                'bg-green-100 text-green-800': user.user_type === 'hospital'
                                            }">
                                            {{ user.user_type === 'hospital' ? 'Healthcare Facility' : user.user_type.charAt(0).toUpperCase() + user.user_type.slice(1) }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                            Not Set
                                        </span>
                                        <p class="text-xs text-gray-600 mt-2">Account type cannot be changed</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button 
                                    type="submit"
                                    :disabled="updatingProfile"
                                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <svg v-if="updatingProfile" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ updatingProfile ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Change Password</h2>
                        
                        <form @submit.prevent="updatePassword">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                                    <input 
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                                    <input 
                                        v-model="passwordForm.password"
                                        type="password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                                    <input 
                                        v-model="passwordForm.password_confirmation"
                                        type="password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button 
                                    type="submit"
                                    :disabled="updatingPassword"
                                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <svg v-if="updatingPassword" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ updatingPassword ? 'Updating...' : 'Update Password' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Danger Zone -->
                    <div class="bg-white rounded-xl shadow-md border-2 border-red-200 p-6">
                        <h2 class="text-xl font-bold text-red-900 mb-2">Danger Zone</h2>
                        <p class="text-sm text-gray-600 mb-4">Irreversible and destructive actions</p>
                        
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-red-900">Delete Account</h3>
                                    <p class="text-sm text-red-700 mt-1">
                                        Permanently delete your account and all associated data. This action cannot be undone.
                                    </p>
                                </div>
                                <button 
                                    @click="showDeleteConfirmation = true"
                                    class="ml-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium text-sm"
                                >
                                    Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Account Info Card -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-md p-6 text-white">
                        <h3 class="font-bold text-lg mb-4">Account Information</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-blue-200">Member Since</p>
                                <p class="font-semibold">{{ formatDate(user.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-blue-200">Account ID</p>
                                <p class="font-mono text-sm">{{ user.id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="font-bold text-gray-900 mb-4">Quick Links</h3>
                        <div class="space-y-2">
                            <Link 
                                href="/privacy"
                                class="flex items-center text-gray-700 hover:text-blue-600 transition py-2"
                            >
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Privacy Settings
                            </Link>
                            <a 
                                href="/"
                                class="flex items-center text-gray-700 hover:text-blue-600 transition py-2"
                            >
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6" @click.stop>
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3 mr-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900">Delete Account</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                This will permanently delete your account and all associated data. This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="deleteAccount">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm with your password</label>
                            <input 
                                v-model="deleteForm.password"
                                type="password"
                                placeholder="Enter your password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                required
                            />
                        </div>

                        <div class="flex gap-3">
                            <button 
                                type="button"
                                @click="showDeleteConfirmation = false"
                                class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                                :disabled="deleting"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="deleting"
                                class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                            >
                                <svg v-if="deleting" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ deleting ? 'Deleting...' : 'Delete Forever' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    user: Object
});

const page = usePage();

const profileForm = reactive({
    name: props.user.name,
    email: props.user.email
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const deleteForm = reactive({
    password: ''
});

const updatingProfile = ref(false);
const updatingPassword = ref(false);
const deleting = ref(false);
const showDeleteConfirmation = ref(false);

onMounted(() => {
    console.log('[AccountSettings] Component mounted', {
        user_id: props.user?.id,
        user_type: props.user?.user_type,
        email_verified: !!props.user?.email_verified_at,
        user_object: props.user
    });

    if (page.props.errors && Object.keys(page.props.errors).length > 0) {
        console.error('[AccountSettings] Errors detected on page load', page.props.errors);
    }
});

const updateProfile = () => {
    console.log('[AccountSettings] Updating profile', {
        name_changed: profileForm.name !== props.user.name,
        email_changed: profileForm.email !== props.user.email
    });

    updatingProfile.value = true;

    router.patch('/profile', profileForm, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('[AccountSettings] Profile updated successfully');
        },
        onError: (errors) => {
            console.error('[AccountSettings] Profile update failed', errors);
        },
        onFinish: () => {
            updatingProfile.value = false;
        }
    });
};

const updatePassword = () => {
    if (!confirm('Are you sure you want to change your password?')) {
        console.log('[AccountSettings] Password change cancelled by user');
        return;
    }

    console.log('[AccountSettings] Updating password');
    updatingPassword.value = true;

    router.put('/password', passwordForm, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('[AccountSettings] Password updated successfully');
            passwordForm.current_password = '';
            passwordForm.password = '';
            passwordForm.password_confirmation = '';
        },
        onError: (errors) => {
            console.error('[AccountSettings] Password update failed', errors);
        },
        onFinish: () => {
            updatingPassword.value = false;
        }
    });
};

const deleteAccount = () => {
    console.log('[AccountSettings] Account deletion initiated');
    deleting.value = true;

    router.delete('/profile', {
        data: deleteForm,
        preserveScroll: true,
        onSuccess: () => {
            console.log('[AccountSettings] Account deleted successfully');
        },
        onError: (errors) => {
            console.error('[AccountSettings] Account deletion failed', errors);
            deleting.value = false;
        },
        onFinish: () => {
            showDeleteConfirmation.value = false;
        }
    });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
