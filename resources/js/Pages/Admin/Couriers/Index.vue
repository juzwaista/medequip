<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">MedEquip Express Fleet</h1>
                    <p class="text-gray-600 mt-2">Provision and manage Courier driver accounts</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Create Courier Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Onboard New Driver</h2>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Personal Info -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input 
                                    v-model="form.name"
                                    type="text" 
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                                    required
                                >
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input 
                                    v-model="form.email"
                                    type="email" 
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                                    required
                                >
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input 
                                    v-model="form.phone_number"
                                    type="text" 
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                                    required
                                >
                                <p v-if="form.errors.phone_number" class="mt-1 text-sm text-red-600">{{ form.errors.phone_number }}</p>
                            </div>

                            <hr class="my-4 border-gray-200">
                            
                            <!-- Vehicle details -->
                            <h3 class="text-md font-semibold text-gray-800 mb-2">Vehicle Details</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                    <select 
                                        v-model="form.vehicle_type"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                                    >
                                        <option value="Motorcycle">Motorcycle</option>
                                        <option value="Van">Van</option>
                                        <option value="Truck">Truck</option>
                                        <option value="Bicycle">Bicycle</option>
                                    </select>
                                    <p v-if="form.errors.vehicle_type" class="mt-1 text-sm text-red-600">{{ form.errors.vehicle_type }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number</label>
                                    <input 
                                        v-model="form.plate_number"
                                        type="text" 
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                                        placeholder="ABC-1234"
                                    >
                                    <p v-if="form.errors.plate_number" class="mt-1 text-sm text-red-600">{{ form.errors.plate_number }}</p>
                                </div>
                            </div>

                            <hr class="my-4 border-gray-200">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Temporary Password</label>
                                <input 
                                    v-model="form.password"
                                    type="text" 
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-gray-50"
                                    readonly
                                >
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 mt-4"
                            >
                                {{ form.processing ? 'Provisioning...' : 'Provision Courier Account' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of Couriers -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                            <h2 class="text-xl font-bold text-gray-900">Active Fleet</h2>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">{{ couriers.length }} Drivers</span>
                        </div>
                        
                        <div v-if="couriers.length > 0" class="divide-y divide-gray-200">
                            <div v-for="driver in couriers" :key="driver.id" class="p-6 hover:bg-gray-50 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="h-12 w-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center text-white shadow-sm">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-lg">{{ driver.name }}</p>
                                            <div class="flex flex-col sm:flex-row sm:items-center text-sm text-gray-500 mt-1 sm:space-x-4">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    {{ driver.email }}
                                                </span>
                                                <span v-if="driver.phone_number" class="flex items-center mt-1 sm:mt-0">
                                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                    {{ driver.phone_number }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-800': driver.courier?.status === 'active',
                                                'bg-yellow-100 text-yellow-800': driver.courier?.status === 'inactive',
                                                'bg-red-100 text-red-800': driver.courier?.status === 'suspended'
                                            }">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                                                :class="{
                                                    'bg-green-500': driver.courier?.status === 'active',
                                                    'bg-yellow-500': driver.courier?.status === 'inactive',
                                                    'bg-red-500': driver.courier?.status === 'suspended'
                                                }"></span>
                                            {{ driver.courier?.status?.toUpperCase() || 'UNKNOWN' }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="driver.courier" class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-sm">
                                    <div class="flex space-x-6">
                                        <div>
                                            <span class="text-gray-500 font-medium">Vehicle:</span> 
                                            <span class="ml-1 text-gray-900 font-semibold">{{ driver.courier.vehicle_type || 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 font-medium">Plate:</span> 
                                            <span class="ml-1 text-gray-900 font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ driver.courier.plate_number || 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-gray-400 text-xs">
                                        Joined {{ new Date(driver.created_at).toLocaleDateString() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-16 text-center text-gray-500 bg-white">
                            <div class="bg-gray-100 h-20 w-20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No Couriers Provisioned</h3>
                            <p class="text-sm">Get started by creating a new driver account using the panel.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    couriers: Array
});

const generatePassword = () => {
    return 'Express@' + Math.random().toString(36).slice(-6).toUpperCase() + '!';
};

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    vehicle_type: 'Motorcycle',
    plate_number: '',
    password: generatePassword(),
});

const submit = () => {
    form.post(route('admin.couriers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'email', 'phone_number', 'plate_number');
            form.password = generatePassword();
        },
    });
};
</script>
