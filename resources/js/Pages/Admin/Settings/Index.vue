<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Global Platform Settings</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Platform Fee Setting -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-2xl">
                    <div class="p-6 text-gray-900 border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">Financial Settings</h3>
                        <p class="text-sm text-gray-600 mb-6">
                            Adjust the global commission rate that the platform deducts from distributor sales when payouts are released to sellers.
                        </p>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label for="platform_fee_percent" class="block font-medium text-sm text-gray-700">
                                    Platform Fee Rate (%)
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        id="platform_fee_percent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="block w-full pr-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        v-model="form.platform_fee_percent"
                                        required
                                    />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p v-if="form.errors.platform_fee_percent" class="text-sm text-red-600 mt-2">
                                    {{ form.errors.platform_fee_percent }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="bg-blue-50 p-4 border-t border-blue-100 flex items-start">
                        <svg class="h-6 w-6 text-blue-600 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-blue-800">
                            <strong>Note:</strong> Changes to the platform fee rate only apply to <em>new payments processed after the change</em>. Existing orders with funds still held by the platform will use the fee rate that was active when they were paid.
                        </p>
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
    platformFeePercent: Number,
});

const form = useForm({
    platform_fee_percent: props.platformFeePercent || 5,
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>
