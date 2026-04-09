<template>
    <component :is="layoutComponent">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
                <p class="text-gray-600 mt-2">Manage your account preferences and security</p>
            </div>

            <!-- Success/Info/Error Messages -->
            <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center text-green-800">
                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.flash.info" class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center text-blue-800 font-medium">
                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ $page.props.flash.info }}
            </div>

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
                    
                    <!-- NEW EMAIL VERIFICATION BOX (Visible only when pending_email exists) -->
                    <div v-if="user.pending_email" class="bg-blue-600 rounded-xl shadow-lg p-6 text-white overflow-hidden relative">
                        <div class="relative z-10">
                            <h2 class="text-xl font-black mb-1 flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Verify New Email Address
                            </h2>
                            <p class="text-blue-100 text-sm font-medium mb-6">
                                We sent a 6-digit code to <span class="text-white font-bold underline decoration-blue-400 underline-offset-4">{{ user.pending_email }}</span>. Enter it below to complete the primary email change.
                            </p>
                            
                            <form @submit.prevent="verifyNewEmail" class="flex flex-col sm:flex-row gap-4 items-end">
                                <div class="flex-1 w-full">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-blue-200 mb-2">6-Digit Security Code</label>
                                    <input 
                                        v-model="verificationForm.otp"
                                        type="text"
                                        maxlength="6"
                                        placeholder="000000"
                                        class="w-full bg-blue-700/50 border-2 border-blue-500/50 rounded-xl px-4 py-3 text-center text-2xl font-black tracking-[0.5em] focus:border-white focus:ring-0 placeholder:text-blue-400 transition-all uppercase"
                                        required
                                    />
                                </div>
                                <button 
                                    type="submit"
                                    :disabled="verifyingEmail || verificationForm.otp.length !== 6"
                                    class="w-full sm:w-auto h-[52px] px-8 bg-white text-blue-700 font-black rounded-xl hover:bg-blue-50 transition-all shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                                >
                                    <span v-if="verifyingEmail">Verifying...</span>
                                    <span v-else>Confirm Change</span>
                                </button>
                            </form>
                        </div>
                        <!-- Decorative background element -->
                        <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-blue-500/20 rounded-full blur-3xl"></div>
                    </div>

                    <!-- Profile Information -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <div class="w-2 h-6 bg-blue-600 rounded-full mr-3"></div>
                            Profile Information
                        </h2>
                        
                        <form @submit.prevent="updateProfile">
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Full Name</label>
                                    <input 
                                        v-model="profileForm.name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Username</label>
                                    <input
                                        v-model="profileForm.username"
                                        type="text"
                                        required
                                        autocomplete="username"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                    />
                                    <p class="text-[11px] text-gray-400 mt-2 font-medium">Unique sign-in name. 4–20 characters, lowercase only.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Current Email Address</label>
                                    <input 
                                        v-model="profileForm.email"
                                        type="email"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        :class="{'border-blue-200 ring-2 ring-blue-50/50': user.pending_email}"
                                        required
                                    />
                                    <div class="flex items-center mt-2 gap-3">
                                        <p v-if="user.email_verified_at" class="text-[10px] font-black uppercase text-green-600 bg-green-50 px-2 py-1 rounded-md flex items-center tracking-widest">
                                            <svg class="h-3 w-3 mr-1 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            Verified
                                        </p>
                                        <p v-if="user.pending_email" class="text-[10px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded-md tracking-widest animate-pulse">
                                            Change Pending: {{ user.pending_email }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Tax Identification Number (TIN)</label>
                                    <input 
                                        v-model="profileForm.tin"
                                        type="text"
                                        @input="formatTIN"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="000-000-000-000"
                                    />
                                    <p class="text-[11px] text-gray-400 mt-2 font-medium italic">Philippine format: 000-000-000-000</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Contact Number *</label>
                                    <input 
                                        v-model="profileForm.phone_number"
                                        type="tel"
                                        pattern="09[0-9]{9}"
                                        maxlength="11"
                                        @input="sanitizePhoneNumber"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="09123456789"
                                        required
                                    />
                                    <p class="text-[11px] text-gray-400 mt-2 font-medium italic">11-digit starting with 09</p>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button 
                                    type="submit"
                                    :disabled="updatingProfile"
                                    class="bg-slate-900 text-white px-8 py-3 rounded-xl hover:bg-black transition-all font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center shadow-lg active:scale-95"
                                >
                                    <svg v-if="updatingProfile" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ updatingProfile ? 'Saving...' : 'Update Settings' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <div class="w-2 h-6 bg-slate-400 rounded-full mr-3"></div>
                            Change Password
                        </h2>
                        
                        <form @submit.prevent="updatePassword">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Current Password</label>
                                    <input 
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        required
                                    />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">New Password</label>
                                        <input 
                                            v-model="passwordForm.password"
                                            type="password"
                                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide text-[11px]">Confirm New Password</label>
                                        <input 
                                            v-model="passwordForm.password_confirmation"
                                            type="password"
                                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button 
                                    type="submit"
                                    :disabled="updatingPassword"
                                    class="bg-slate-100 text-slate-700 border border-slate-200 px-8 py-3 rounded-xl hover:bg-slate-200 transition-all font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center active:scale-95"
                                >
                                    {{ updatingPassword ? 'Updating...' : 'Update Password' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Deactivate Account -->
                    <div class="bg-white rounded-xl shadow-md border-2 border-amber-200 p-6 overflow-hidden relative">
                        <div class="relative z-10">
                            <h2 class="text-xl font-bold text-amber-900 mb-2">Deactivate Account</h2>
                            <p class="text-sm text-gray-600 mb-4 font-medium">Temporarily disable your account</p>
                            
                            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-amber-900">Initiate Deactivation</h3>
                                        <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                                            Your account will be deactivated immediately. After 30 days, it will be permanently deleted. You can reactivate anytime within those 30 days by logging back in.
                                        </p>
                                    </div>
                                    <button 
                                        @click="showDeactivateConfirmation = true"
                                        class="w-full sm:w-auto px-6 py-3 bg-white border-2 border-amber-500 text-amber-600 rounded-xl hover:bg-amber-500 hover:text-white transition-all font-black text-[11px] uppercase tracking-widest shadow-sm"
                                    >
                                        Deactivate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Account Info Card -->
                    <div class="bg-gradient-to-br from-indigo-700 via-blue-700 to-blue-600 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="font-black text-xl mb-6 tracking-tight">Account Summary</h3>
                            <div class="space-y-5">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-200 opacity-80">Member Since</p>
                                    <p class="font-black text-lg">{{ formatDate(user.created_at) }}</p>
                                </div>
                                <div class="pt-4 border-t border-white/10">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-200 opacity-80">Reference ID</p>
                                    <p class="font-mono text-sm opacity-90">{{ user.id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 -mb-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                        <h3 class="font-black text-xs uppercase tracking-widest text-gray-400 mb-4">Quick Navigation</h3>
                        <div class="space-y-1">
                            <Link 
                                href="/privacy"
                                class="flex items-center text-sm font-bold text-gray-700 hover:text-blue-600 hover:bg-blue-50 px-3 py-3 rounded-xl transition-all group"
                            >
                                <svg class="h-4 w-4 mr-3 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Privacy & Privacy
                            </Link>
                            <Link 
                                :href="user.role === 'courier' ? '/courier/dashboard' : user.role === 'distributor' || user.role === 'staff' ? '/owner/dashboard' : '/'"
                                class="flex items-center text-sm font-bold text-gray-700 hover:text-blue-600 hover:bg-blue-50 px-3 py-3 rounded-xl transition-all group"
                            >
                                <svg class="h-4 w-4 mr-3 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                {{ user.role === 'courier' ? 'Courier Fleet Dashboard' : user.role === 'distributor' || user.role === 'staff' ? 'Enterprise Dashboard' : 'MedEquip Marketplace' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deactivate Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeactivateConfirmation" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[100] px-4">
                <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 border border-slate-100" @click.stop>
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="bg-amber-100 rounded-2xl p-4 mb-4">
                            <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Confirm Deactivation</h3>
                        <p class="text-sm text-slate-500 mt-2 font-medium leading-relaxed px-4">
                            You'll be logged out immediately. Your data will be kept for <span class="text-slate-900 font-black">30 days</span> before permanent deletion.
                        </p>
                    </div>

                    <form @submit.prevent="deactivateAccount">
                        <div class="mb-6">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 text-center">Verify Identity with Password</label>
                            <input 
                                v-model="deactivateForm.password"
                                type="password"
                                placeholder="Enter your password"
                                class="w-full px-6 py-4 border-2 border-slate-100 bg-slate-50 rounded-2xl focus:ring-0 focus:border-amber-500 focus:bg-white transition-all text-center font-bold"
                                required
                            />
                        </div>

                        <div class="flex flex-col gap-3">
                            <button 
                                type="submit"
                                :disabled="deactivating"
                                class="w-full py-4 bg-amber-600 text-white rounded-2xl hover:bg-amber-700 transition-all font-black shadow-lg shadow-amber-200 active:scale-95 disabled:opacity-50"
                            >
                                <span v-if="deactivating">Processing...</span>
                                <span v-else>Confirm & Deactivate</span>
                            </button>
                            <button 
                                type="button"
                                @click="showDeactivateConfirmation = false"
                                class="w-full py-3 text-slate-400 font-bold hover:text-slate-600 transition-all"
                                :disabled="deactivating"
                            >
                                Nevermind, go back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </component>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { router, Link, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import CourierLayout from '@/Layouts/CourierLayout.vue';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    user: Object
});

const page = usePage();

// Render the correct layout based on user role
const layoutComponent = computed(() => {
    const role = props.user?.role || page.props.auth?.user?.role;
    if (role === 'courier') return CourierLayout;
    if (role === 'distributor' || role === 'staff') return OwnerLayout;
    return MainLayout;
});

const profileForm = reactive({
    name: props.user.name,
    username: props.user.username || '',
    email: props.user.email,
    phone_number: props.user.phone_number || '',
    tin: props.user.tin || '',
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const verificationForm = useForm({
    otp: ''
});

const updatingProfile = ref(false);
const updatingPassword = ref(false);
const deactivating = ref(false);
const verifyingEmail = ref(false);
const showDeactivateConfirmation = ref(false);

const deactivateForm = reactive({ password: '' });

onMounted(() => {
    if (page.props.errors && Object.keys(page.props.errors).length > 0) {
        console.error('[AccountSettings] Errors detected on page load', page.props.errors);
    }
});

const updateProfile = () => {
    updatingProfile.value = true;

    router.patch('/profile', profileForm, {
        preserveScroll: true,
        onSuccess: () => {
        },
        onError: (errors) => {
            console.error('[AccountSettings] Profile update failed', errors);
        },
        onFinish: () => {
            updatingProfile.value = false;
        }
    });
};

const verifyNewEmail = () => {
    verifyingEmail.value = true;
    verificationForm.post('/profile/verify-email', {
        preserveScroll: true,
        onSuccess: () => {
            verificationForm.otp = '';
        },
        onFinish: () => {
            verifyingEmail.value = false;
        }
    });
};

const updatePassword = () => {
    if (!confirm('Are you sure you want to change your password?')) {
        return;
    }

    updatingPassword.value = true;

    router.put('/password', passwordForm, {
        preserveScroll: true,
        onSuccess: () => {
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

const deactivateAccount = () => {
    deactivating.value = true;
    router.post('/profile/deactivate', deactivateForm, {
        preserveScroll: true,
        onError: (errors) => {
            console.error('[AccountSettings] Deactivation failed', errors);
            deactivating.value = false;
        },
        onFinish: () => {
            showDeactivateConfirmation.value = false;
        }
    });
};

const formatTIN = (e) => {
    let value = e.target.value.replace(/\D/g, ''); // Remove all non-digits
    
    // Limit to 12 digits
    if (value.length > 12) {
        value = value.slice(0, 12);
    }
    
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 3 === 0) {
            formatted += '-';
        }
        formatted += value[i];
    }
    
    profileForm.tin = formatted;
};

const sanitizePhoneNumber = (e) => {
    let value = e.target.value.replace(/\D/g, ''); // Remove all non-digits
    if (value.length > 11) value = value.slice(0, 11);
    profileForm.phone_number = value;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
