<template>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo -->
            <Link href="/" class="flex justify-center mb-6">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-indigo-600 text-white flex items-center justify-center rounded-xl shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-indigo-800">
                        MedEquip <span class="text-gray-900">Corporate</span>
                    </span>
                </div>
            </Link>
            
            <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
                Unlock Wholesale Pricing
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Register your business to instantly view bulk pricing tiers.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Personal Info -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Contact Person</h3>
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input id="name" v-model="form.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Work Email</label>
                                <input id="email" v-model="form.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                    <input id="password" v-model="form.password" type="password" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                    <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</div>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <input id="password_confirmation" v-model="form.password_confirmation" type="password" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Info -->
                    <div class="pt-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Business Details</h3>
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700">Company / Hospital Name</label>
                                <input id="company_name" v-model="form.company_name" type="text" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                <div v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</div>
                            </div>

                            <div>
                                <label for="business_type" class="block text-sm font-medium text-gray-700">Organization Type</label>
                                <select id="business_type" v-model="form.business_type" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="" disabled>Select a type...</option>
                                    <option value="Hospital">Hospital / Clinic</option>
                                    <option value="Pharmacy">Pharmacy</option>
                                    <option value="Distributor">Wholesaler / Distributor</option>
                                    <option value="Government">Government / LGU</option>
                                    <option value="Other">Other Enterprise</option>
                                </select>
                                <div v-if="form.errors.business_type" class="mt-1 text-sm text-red-600">{{ form.errors.business_type }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4 mt-2">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1 md:flex md:justify-between">
                                <p class="text-sm text-indigo-700">
                                    <strong>No documents required yet.</strong> You can browse wholesale prices immediately. We will only ask for your TIN and Permits when you are ready to request a formal quote or submit a Purchase Order.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Creating Account...' : 'Create Business Account' }}
                        </button>
                    </div>
                    
                    <div class="text-center text-sm text-gray-600">
                        Already have an account? 
                        <Link href="/login" class="font-bold text-indigo-600 hover:text-indigo-500">Sign in instead</Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    company_name: '',
    business_type: '',
});

const submit = () => {
    form.post(route('corporate.register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
